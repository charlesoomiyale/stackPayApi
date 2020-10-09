<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ForGotPassword extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $otp;

    /**
     * Create a new message instance.
     *
     * @param $name
     * @param $otp
     */
    public function __construct($name,$otp)
    {
        $this->name = $name;
        $this->otp = $otp;
    }
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this->from('hello@isparkng.com', env('APP_NAME'))
            ->subject('Complete your password reset request')
            ->view('emails.password_forgot',['name' => $this->name,'otp' => $this->otp]);
    }
}
