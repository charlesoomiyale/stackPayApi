<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordReset extends Mailable
{
    use Queueable, SerializesModels;

    public $name;

    /**
     * Create a new message instance.
     *
     * @param $name
     * @param $otp
     */
    public function __construct($name)
    {
        $this->name = $name;
    }
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this->from('hello@isparkng.com', env('APP_NAME'))
            ->subject('Your password has been changed')
            ->view('emails.password_reset',['name' => $this->name]);
    }
}
