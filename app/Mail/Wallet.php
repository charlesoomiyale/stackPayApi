<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Wallet extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $message;
    public $action;

    /**
     * Create a new message instance.
     *
     * @param $name
     * @param $message
     * @param $action
     */
    public function __construct($name,$message,$action)
    {
        $this->name = $name;
        $this->message = $message;
        $this->action = $action;
    }
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this->from('hello@isparkng.com', env('APP_NAME'))
            ->subject($this->action)
            ->view('emails.receipt',[
                'name' => $this->name,
            ]);
    }
}
