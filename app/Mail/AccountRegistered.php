<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountRegistered extends Mailable
{
    use Queueable, SerializesModels;

    public $name;

    /**
     * Create a new message instance.
     *
     * @param $name
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
                ->subject('Welcome')
                ->view('emails.welcome',['name' => $this->name]);
    }
}
