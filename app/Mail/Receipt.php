<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Receipt extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $service;
    public $type;
    public $number;
    public $ref;
    public $amount;

    /**
     * Create a new message instance.
     *
     * @param $name
     * @param $service
     * @param $type
     * @param $number
     * @param $ref
     * @param $amount
     */
    public function __construct($name,$service,$type,$number,$ref,$amount)
    {
        $this->name = $name;
        $this->service = $service;
        $this->type = $type;
        $this->number = $number;
        $this->ref = $ref;
        $this->amount = $amount;
    }
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this->from('hello@isparkng.com', env('APP_NAME'))
            ->subject('Purchase receipt')
            ->view('emails.receipt',[
                'name' => $this->name,
                'service' => $this->service,
                'type' => $this->type,
                'number' => $this->number,
                'ref' => $this->ref,
                'amount' => $this->amount,
            ]);
    }
}
