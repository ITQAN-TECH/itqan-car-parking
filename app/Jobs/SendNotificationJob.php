<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Models\User;
use App\services\FCMService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The collection of recipients (User, Customer, Investor models, etc.).
     *
     * @var \Illuminate\Support\Collection
     */
    protected $recipients;

    /**
     * The specific Notification object (e.g., RejectBookingNotification, AcceptHouseRequestNotification).
     *
     * @var \Illuminate\Notifications\Notification|null
     */
    protected $notification;

    /**
     * The title of the FCM notification.
     *
     * @var string|null
     */
    protected $title;

    /**
     * The body/description of the FCM notification.
     *
     * @var string|null
     */
    protected $body;

    /**
     * Create a new job instance.
     *
     * @param  \Illuminate\Notifications\Notification|null  $notification  (Optional: DB notification object)
     * @param  string|null  $title  (Optional: FCM notification title)
     * @param  string|null  $body  (Optional: FCM notification body)
     * @return void
     */
    public function __construct(
        Collection $recipients,
        $notification = null,
        ?string $title = null,
        ?string $body = null
    ) {
        $this->recipients = $recipients;
        $this->notification = $notification;
        $this->title = $title;
        $this->body = $body;
    }

    /**
     * Execute the job.
     *
     * @param  \App\services\FCMService  $customerFCMService  (General/Customer FCM Service)
     * @return void
     */
    public function handle(FCMService $fcmService)
    {
        // 1. Send the database notification (if $notification object is provided)
        if ($this->notification) {
            Notification::send($this->recipients, $this->notification);
        }

        // 2. Send FCM notification (if $title and $body are provided)
        if ($this->title && $this->body) {

            // التكرار على المستلمين وإرسال FCM حسب نوع المستخدم
            foreach ($this->recipients as $recipient) {
                // إعادة تحميل الـ model من قاعدة البيانات لضمان تحميل العلاقات بشكل صحيح
                if ($recipient instanceof Employee) {
                    $recipient = Employee::with('fcmTokens')->find($recipient->id);
                } elseif ($recipient instanceof User) {
                    $recipient = User::find($recipient->id);
                }

                if (! $recipient) {
                    continue; // تخطي إذا لم يتم العثور على المستخدم
                }

                $shouldReceiveFCM = $recipient->receive_notifications ?? true;
                // **تخطي إرسال الإشعار إذا كان المستخدم قد أوقف الإشعارات**
                if ($recipient instanceof Employee) {
                    if (! $shouldReceiveFCM) {
                        continue;
                    }
                }

                // الحصول على جميع رموز FCM للمستخدم
                $fcmTokens = [];
                if ($recipient instanceof Employee) {
                    // استخدام العلاقة المحملة أو تحميلها من قاعدة البيانات
                    $fcmTokens = $recipient->fcmTokens()->pluck('token')->toArray();
                } elseif ($recipient instanceof User) {
                    // للمستخدمين (Admins) - إذا كان لديهم fcm_token مباشرة (للتوافق مع الكود القديم)
                    $fcmToken = $recipient->fcm_token ?? null;
                    if ($fcmToken) {
                        $fcmTokens = [$fcmToken];
                    }
                }

                if (empty($fcmTokens)) {
                    continue; // تخطي إذا لم يكن هناك رموز FCM
                }

                // إرسال الإشعار لجميع الرموز
                foreach ($fcmTokens as $fcmToken) {
                    if (empty($fcmToken)) {
                        continue;
                    }

                    try {
                        if ($recipient instanceof Employee) {
                            // نستخدم FCMService (للعملاء)
                            $result = $fcmService->sendNotification($fcmToken, $this->title, $this->body);
                        } elseif ($recipient instanceof User) {
                            // نستخدم FCMService لموديل User (Admins)
                            $result = $fcmService->sendNotification($fcmToken, $this->title, $this->body);
                        }
                    } catch (\Exception $e) {
                        Log::error('Error sending FCM notification: '.$e->getMessage(), [
                            'recipient_id' => $recipient->id,
                            'recipient_type' => get_class($recipient),
                            'token' => substr($fcmToken, 0, 20).'...',
                        ]);
                    }
                }
            }
        }
    }
}
