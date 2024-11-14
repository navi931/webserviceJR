<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class enviarCorreo extends Mailable
{
    use Queueable, SerializesModels;

    public $call;

    public function __construct($call)
    {
      $this->call = $call;
    }

    public function build()
    {
      $id_reservacion_temp = $this->call->IDReservacion;
      if(1 == $this->call->BanderaPrepago || '1' == $this->call->BanderaPrepago)
      {
        $prepago_temp_string = "---Prepay---";
      }
      else
      {
        $prepago_temp_string = "";
      }
      return $this->view('enviarCorreo')->subject("Reserva de Priceless / Priceless Reservation -- ID:$id_reservacion_temp $prepago_temp_string");
    }
}
