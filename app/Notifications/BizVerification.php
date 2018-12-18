<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class BizVerification extends Notification
{
    use Queueable;
    
    use Notifiable;

    public $businessHash;
    public $orderHash;
    /**
     * Create a new notification instance.
     *
     * @return void
     */

     public function __construct($order_hash,$business_hash)
        {
            //\Log::info('hello world');
            $this->orderHash = $order_hash;
            $this->businessHash= $business_hash;
            //dd($this->biz_hash);
        }

    // public function __construct()
    // {
    //     //
    // }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {   
        

    

        $url =  route('api.bizverification.confirm', ['businessHash' => $this->businessHash,'orderHash' => $this->orderHash]);
        // $url = url('api/biz-verification/confirm'.$this->orderHash);
        //dd($url);
        return (new MailMessage)
            ->greeting('business Verification ')
            ->line('you need to verify your business by clicking on this link')
            ->action('Verify', $url);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
