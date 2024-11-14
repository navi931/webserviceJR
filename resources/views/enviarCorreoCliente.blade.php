<?php

$host = env('APP_URL');

            $options = array(
                'ssl'=>array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ),
            );
            $context = stream_context_create($options);
            $token = json_decode( file_get_contents( $host."/api/getToken?key=HJFKDISY127364BDK&minutes=60", false, $context));

$ApellidoPaterno_webservice = str_replace(" ","%20","$call->ApellidoPaterno");

$data2 = @file_get_contents($host."/api/getReserva?token=$token&codigo=$call->IDReservacion&apellido=$ApellidoPaterno_webservice&temporal=0", false, $context);

$data = json_decode($data2);

function Cliente($nom, $edad, $tel, $email)
{
    $cad = $nom;
    if($edad == 21)
    {
        $cad = $cad.'<br>'.$edad.' años o más';
    }
    else
    {
        $cad = $cad.'<br>'.$edad.' años';
    }
    if($tel != null)
    {
        $cad = $cad.'<br>'.$tel;
    }
    return $cad.'<br><label style="color: black; ">'.$email.'</label>';
}

  function Detalles($tarifa, $codigo, $agencia, $na, $comisionista, $nc, $leal, $paypal, $banp, $total, $moneda)
  {
      $cad = 'Tarifa / Rate: '.$tarifa;
      if($codigo != null)
      {
          $cad = $cad.'<br>Codigo / Code: '.$codigo;
      }
      if($leal != "0")
      {
          $cad = $cad.'<br>Cliente Leal / Loyal Customer: '.$leal;
      }
      if($paypal != null && $banp != 0)
      {
          $cad = $cad.'<br><b>Prepago/Prepay</b><br>ID Pago; '.$paypal;
      }
      if($agencia != "0")
      {
          $cad = $cad.'<br>Agencia / Agency: '.$na.' ID: '.$agencia;
      }
      if($comisionista != "0")
      {
          $cad = $cad.'<br>Comisionista / Agency: '.$nc.' ID: '.$comisionista;
      }
      if($total != null)
      {
          $cad = $cad.'<br>Total: $'.$total;
      }
      if($moneda != null)
      {
        if ($moneda == 'MN')
        {
          $moneda = 'MXN';
        }
          $cad = $cad.'<br>Moneda / Currency: '.$moneda;
      }
      return $cad;
  }

  function FechaCadena($fecha)
  {
      $f = explode(":00.000", $fecha);

      // Cadena de fecha en formato YYYY-MM-DD HH:MM
      $dateString = $f[0];

      // Convertir la cadena de fecha a un objeto DateTime
      $dateTime = DateTime::createFromFormat('Y-m-d H:i', $dateString);

      // Verificar si la conversión fue exitosa
      if ($dateTime === false) {
          echo "La conversión de la cadena de fecha falló.";
      } else {
          // Array con los nombres de los días de la semana
          $daysOfWeek = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

          // Array con los nombres de los meses
          $months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

          // Obtener el nombre del día de la semana
          $dayOfWeek = $daysOfWeek[$dateTime->format('w')];

          // Obtener el nombre del mes
          $month = $months[$dateTime->format('n') - 1];

          // Formatear la fecha en el formato deseado
          $formattedDate = $dayOfWeek . ', ' . $month . ' ' . $dateTime->format('d, Y H:i:s') . ' hrs';
      }

      return $formattedDate;
  }

  function Oficina($idof, $fecha, $host, $token, $context)
  {
      $of = file_get_contents($host.'/api/getDomicilioOficina?token='.$token.'&id='.$idof, false, $context);
      $of = json_decode($of);
      $cad = $of->nombre.'<br>'.$fecha;
      $cad = $cad.'<br> Calle: '.$of->Calle.'<br> Numero Exterior: '.$of->NumExt.'<br> Numero Interior: '.$of->NumInt.'<br> Colonia: '.$of->Colonia.'<br> Ciudad: '.$of->Ciudad.'<br> CP: '.$of->CodigoPostal;
      if($of->Telefono1 != null)
      {
          $cad = $cad.'<br>'.$of->Telefono1;
      }
      if($of->Telefono2 != null)
      {
          $cad = $cad.'<br>'.$of->Telefono2;
      }
      return $cad;
  }

  function Desglose($data)
  {
      $cad = '';
      if($data->CantidadMes > 0)
      {
          $cad = $cad.'<tr class="fila"><td style="padding-left: 0.5rem; padding-right: 0.5rem;">'.$data->CantidadMes.' Mes(es)/Month Renta/TK @ '.number_format($data->TarifaMesTK,2).'</td><td align="right" style="padding-left: 0.5rem; padding-right: 0.5rem;">$ '.number_format($data->ImporteMesTK,2).'</td></tr>';
      }
      if($data->CantidadSemana > 0)
      {
          $cad = $cad.'<tr class="fila"><td style="padding-left: 0.5rem; padding-right: 0.5rem;">'.$data->CantidadSemana.' Semana(s)/Week Renta/TK @ '.number_format($data->TarifaSemanaTK,2).'</td><td align="right" style="padding-left: 0.5rem; padding-right: 0.5rem;">$ '.number_format($data->ImporteSemanaTK,2).'</td></tr>';
      }
      if($data->CantidadDiaExtra > 0)
      {
          $cad = $cad.'<tr class="fila"><td style="padding-left: 0.5rem; padding-right: 0.5rem;">'.$data->CantidadDiaExtra.' Día(s) Extras/Extra Days Renta/TK @ '.number_format($data->TarifaDiaExtraTK,2).'</td><td align="right" style="padding-left: 0.5rem; padding-right: 0.5rem;">$ '.number_format($data->ImporteDiaExtraTK,2).'</td></tr>';
      }
      if($data->CantidadDia > 0)
      {
          $cad = $cad.'<tr class="fila"><td style="padding-left: 0.5rem; padding-right: 0.5rem;">'.$data->CantidadDia.' Día(s)/Days Renta/TK @ '.number_format($data->TarifaDiaTK,2).'</td><td align="right">$ '.number_format($data->ImporteDiaTK,2).'</td></tr>';
      }
      if($data->CantidadDiaFinSemana > 0)
      {
          $cad = $cad.'<tr class="fila"><td style="padding-left: 0.5rem; padding-right: 0.5rem;">'.$data->CantidadDiaFinSemana.' Día(s) fin de semana/Weekend days Renta/TK @ '.number_format($data->TarifaFinSemanaTK,2).'</td><td align="right" style="padding-left: 0.5rem; padding-right: 0.5rem;">$ '.number_format($data->ImporteDiaFinSemanaTK,2).'</td></tr>';
      }
      if($data->CantidadHora > 0)
      {
          $cad = $cad.'<tr class="fila"><td style="padding-left: 0.5rem; padding-right: 0.5rem;">'.$data->CantidadHora.' Hora(s)/Hours Renta/TK @ '.number_format($data->TarifaHoraTK,2).'</td><td align="right" style="padding-left: 0.5rem; padding-right: 0.5rem;">$ '.number_format($data->ImporteHoraTK,2).'</td></tr>';
      }
      // Coberturas y extras
      $desc = "(Cob)";
      for($i=0; $i<sizeof($data->seguros); $i++)
      {
          if($data->seguros[$i]->Tit == "ERA")
          {
              $desc = "(Ext)";
          }
          if($data->seguros[$i]->Bandera > 0)
          {
              $cad = $cad.'<tr class="fila"><td style="padding-left: 0.5rem; padding-right: 0.5rem;">'.$data->seguros[$i]->Tit.' '.$desc.' '.$data->DiasSeguros.' Dia(s)/Days @ '.number_format($data->seguros[$i]->CostoDia,2).'</td><td align="right" style="padding-left: 0.5rem; padding-right: 0.5rem;">$ '.number_format($data->seguros[$i]->Importe,2).'</td></tr>';
          }
      }

      if($data->CargoDropOff > "0")
      {
          $cad = $cad.'<tr class="fila"><td style="padding-left: 0.5rem; padding-right: 0.5rem;">DropOff</td><td align="right" style="padding-left: 0.5rem; padding-right: 0.5rem;">$ '.number_format($data->CargoDropOff,2).'</td></tr>';
      }
      if($data->CargoPickup > "0")
      {
          $cad = $cad.'<tr class="fila"><td style="padding-left: 0.5rem; padding-right: 0.5rem;">Pickup</td><td align="right" style="padding-left: 0.5rem; padding-right: 0.5rem;">$ '.number_format($data->CargoPickup,2).'</td></tr>';
      }
      if($data->fees->BanderaLCRFee)
      {
          $cad = $cad.'<tr class="fila"><td style="padding-left: 0.5rem; padding-right: 0.5rem;">Total Cargo por Registro Vehicular/License Recoupment Fee</td><td align="right" style="padding-left: 0.5rem; padding-right: 0.5rem;">$ '.number_format($data->fees->CargoLCRFee,2).'</td></tr>';
      }
      if($data->fees->BanderaAirportFee)
      {
          $cad = $cad.'<tr class="fila"><td style="padding-left: 0.5rem; padding-right: 0.5rem;">Cargo Aeroportuario/Airport Charge Fee</td><td align="right" style="padding-left: 0.5rem; padding-right: 0.5rem;">$ '.number_format($data->fees->MontoAirportFee,2).'</td></tr>';
      }
      if($data->fees->BanderaHotelFee)
      {
          $cad = $cad.'<tr class="fila"><td style="padding-left: 0.5rem; padding-right: 0.5rem;">Cargo Hotel/Hotel Charge Fee</td><td align="right" style="padding-left: 0.5rem; padding-right: 0.5rem;">$ '.number_format($data->MontoHotelFee,2).'</td></tr>';
      }
      if($data->fees->CargoSCFee > "0")
      {
          $cad = $cad.'<tr class="fila"><td style="padding-left: 0.5rem; padding-right: 0.5rem;">Cargo Servicio/Service Charge Fee</td><td align="right" style="padding-left: 0.5rem; padding-right: 0.5rem;">$ '.number_format($data->fees->CargoSCFee,2).'</td></tr>';
      }
      if($data->descuento_al_subtotal > "0")
      {
          $cad = $cad.'<tr class="fila"><td style="padding-left: 0.5rem; padding-right: 0.5rem;">Descuento/Discount</td><td align="right" style="padding-left: 0.5rem; padding-right: 0.5rem; font-weight: bold;"><b> - $ '.number_format($data->descuento_al_subtotal,2).'</b></td></tr>';
      }
      //Subtotal
      // $cad = $cad.'<tr class="fila"><td style="padding-left: 0.5rem; padding-right: 0.5rem;"><b>Subtotal:</b></td style="padding-left: 0.5rem; padding-right: 0.5rem;"><td align="right" style="padding-left: 0.5rem; padding-right: 0.5rem;"><b>$ '.number_format($data->Subtotal,2).'</b></td></tr>';
      if($data->MontoIva > "0")
      {
          $cad = $cad.'<tr class="fila"><td style="padding-left: 0.5rem; padding-right: 0.5rem;"><b>IVA ('.number_format(($data->PorIva-1),2).'%)</b></td><td align="right" style="padding-left: 0.5rem; padding-right: 0.5rem;"><b>$ '.number_format($data->MontoIva,2).'</b></td></tr>';
      }
      if ($data->Moneda == 'MN')
      {
        $data->Moneda = 'MXN';
      }

      return $cad = $cad.'<tr class="fila"><td style="padding-left: 0.5rem; padding-right: 0.5rem;"><b>Total:</b></td style="padding-left: 0.5rem; padding-right: 0.5rem;"><td align="right" style="padding-left: 0.5rem; padding-right: 0.5rem;"><b>$ '.number_format($data->Total,2).' '.$data->Moneda.'</b></td></tr>';
  }
?>
<html lang="es">
  <head>
      <meta charset="utf-8">
      <style>
          .btn
          {
              display: inline-block;
              font-weight: 400;
              color: #212529;
              text-align: center;
              vertical-align: middle;
              cursor: pointer;
              -webkit-user-select: none;
              -moz-user-select: none;
              -ms-user-select: none;
              user-select: none;
              background-color: transparent;
              border: 1px solid transparent;
              padding: 0.375rem 0.75rem;
              font-size: 1rem;
              line-height: 1.5;
              border-radius: 0.25rem;
              transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
          }

          a
          {
              text-decoration: none;
          }

          a.btn-primary
          {
              color: white;
              background-color:#747673;
              border-color:#747673;
          }

          .btn:hover
          {
              color: #212529;
              text-decoration: none;
          }
          .btn-primary
          {
              color: #fff;
              background-color:#747673;
              border-color:#747673;
          }

          tr.fila:hover {background-color:#f5f5f5;}

          .btn-primary:hover
          {
              color: #fff;
              background-color: darkgray;
              border-color:  darkgray;
          }

          .btn-primary:focus, .btn-primary.focus
          {
              color: #fff;
              background-color: #0069d9;
              border-color: #0062cc;
              box-shadow: 0 0 0 0.2rem rgba(38, 143, 255, 0.5);
          }
      </style>
  </head>
  <body  style="font-family: Gotham; font-style: normal;">
      <table cellspacing="0" cellpadding="0" align="center" width="700px">
          <tr>
              <td>
                  <table align="center" style="width: 700px; background-color: white;;">
                      <tr>
                          <td>
                              <img src="https://pricelesscarrental.mx/img/logo-3.png" style="height: 90px;">
                          </td>
                          <td align="center">
                              <div style="text-align: center;">
                                  <h3>Hola, {{$data->Nombre}} ¡Gracias por elegir PRICELESS!</h3>
                                  <h3>Hello, {{$data->Nombre}} ¡Thank you for choosing PRICELESS!</h3>
                              </div>
                          </td>
                      </tr>
                  </table>
              </td>
          </tr>
          <tr style="background-color: #0069AE; color: white; text-align: center; padding-top: 0.5rem; padding-bottom: 0.5rem;">
              <td>
                  <h4>Su número de confirmación / Your Confirmation Number:</h4>
                  <h2><b style="color: white;">{{$data->IDReservacion}}</b></h2>
                  <h2><b style="color: white;">Status: {{$data->TipoMovRegistro}}</b></h2>
              </td>
          </tr>
          <tr>
              <table align="center" style="width: 700px; background-color: white; margin-left: auto; margin-right: auto;">
                  <tr>
                      <td>
                          <div style="background-color: #0069AE; color: white; padding-left: 0.5rem; padding-right: 0.5rem;">
                              Cliente / Client:
                          </div>
                          <div style="padding-left: 0.5rem; padding-right: 0.5rem;">
                            <?php echo Cliente($data->NombreCompleto, $data->Edad, $data->Telefono, $data->Email)?>
                          </div>
                      </td>
                  </tr>
              </table>
          </tr>
          <tr>
              <table align="center" style="width: 700px; background-color: white; margin-left: auto; margin-right: auto;">
                  <tr>
                      <td>
                          <div style="background-color: #0069AE; color: white; padding-left: 0.5rem; padding-right: 0.5rem;">
                              Mas detalles / More details:
                          </div>
                          <div style="padding-left: 0.5rem; padding-right: 0.5rem;">
                              <?php echo Detalles($data->IDTarifa, $data->IDCodDescuento, $data->IDAgencia, $data->nombre_agencia, $data->IDComisionista, $data->nombre_comisionista, $data->IDCteLeal, $data->autorizacion_Paypal, $data->BanderaPrepago, number_format($data->Total,2), $data->Moneda) ?>
                          </div>
                      </td>
                  </tr>
              </table>
          </tr>
          <tr>
              <table align="center" style="width: 700px; background-color: white; margin-left: auto; margin-right: auto;">
                  <tr>
                      <td class="text-center">
                          <div style="text-align: center; background-color: #0069AE; color: white; ">
                              {{$data->Descripcion}}, {{$data->IDSIPPCode}}
                          </div>
                      </td>
                  </tr>
                  <tr>
                      <td>
                          <div style="text-align: center;">
                              <img src="https://pricelesscarrental.mx/img/Carros/<?php echo $data->IDSIPPCode?>.png" style="height: 150px;">
                          </div>
                      </td>
                      <td>

                      </td>
                  </tr>
              </table>
          </tr>
          <tr>
              <table align="center" style="padding-top: 0px; padding-bottom: 0px; width: 700px; background-color: white; margin-left: auto; margin-right: auto;">
                  <tr>
                      <td>
                          <div style="background-color: #0069AE; color: white; padding-left: 0.5rem; padding-right: 0.5rem;">
                              Oficina de Entrega / Pickup location:
                          </div>
                          <div style="padding-left: 0.5rem; padding-right: 0.5rem;">
                              <?php echo Oficina($data->IDOficinaReservacion, FechaCadena($data->FechaReservacion), $host, $token, $context) ?>
                          </div>
                      </td>
                  </tr>
              </table>
              <table align="center" style="padding-top: 0px; padding-bottom: 0px; width: 700px; background-color: white; margin-left: auto; margin-right: auto;">
                  <tr>
                      <td>
                          <div style="background-color: #0069AE; color: white; padding-left: 0.5rem; padding-right: 0.5rem;">
                              Oficina de Regreso / Return location:
                          </div>
                          <div style="padding-left: 0.5rem; padding-right: 0.5rem;">
                          <?php echo Oficina($data->IDOficinaRetorno, FechaCadena($data->FechaRetorno), $host, $token, $context)?>
                          </div>
                      </td>
                  </tr>
                  <tr>
                      <td>
                          <div style="padding-left: 0.5rem; padding-right: 0.5rem;">
                              <?php
                              echo "Para cualquier cambio, visite nuestra pagina web / to make any changes, please visit our website https://www.pricelesscarrental.mx/ o si lo prefiere
                              llame a nuestro telefono / or call us $data->Telefono1_plaza (desde Mexico) $data->Telefono2_plaza (From USA) o contactenos por el email / or contact us by email $data->Email_plaza"
                              ?>
                          </div>
                      </td>
                  </tr>
                  <tr>
                  </tr>
              </table>
          </tr>
          <tr>
              <td>
                  <table align="center" style="width: 700px; background-color: white; margin-left: auto; margin-right: auto;">
                      <thead style="background-color: #0069AE; color: white; padding-left: 0.5rem; padding-right: 0.5rem;">
                          <tr>
                              <td colspan="2" style="padding-left: 0.5rem; padding-right: 0.5rem;">
                                  Tarifa / Rate
                              </td>
                          </tr>
                      </thead>
                      <tbody style="padding-left: 0.5rem; padding-right: 0.5rem;">
                          <?php echo Desglose($data) ?>
                      </tbody>
                  </table>
              </td>
          </tr>
          <tr>
              <table align="center" width="700px">
                  <tr>
                      <td align="">
                          <a class="btn btn-primary" href="https://pricelesscarrental.mx/PDF/Confirmacion.php?dt=<?php echo $data->IDReservacion.'_'.$data->ApellidoPaterno?>">
                              Descargar PDF/Download PDF
                          </a>
                      </td>
                  </tr>
                  <tr>
                      <td align="">
                          <a class="btn btn-primary" href="https://www.google.com/maps?q=<?php echo $data->LatitudSalida.','.$data->LongitudSalida?>">
                              Oficina de Salida/Pickup Office (Google Maps)
                          </a>
                      </td>
                  </tr>
                  <tr>
                      <td align="">
                          <a class="btn btn-primary" href="https://www.google.com/maps?q=<?php echo $data->LatitudRegreso.','.$data->LongitudRegreso?>">
                              Oficina de Regreso/Return office (Google Maps)
                          </a>
                      </td>
                  </tr>
                  <tr>
                      <td align="">
                          <a class="btn btn-primary" href="https://pricelesscarrental.mx/Documents/ES/Reservas/CancelarReserva.php">
                              Cancelar Reservación/ Cancel Reservation
                          </a>
                      </td>
                  </tr>
              </table>
          </tr>
          <tr >
              <table align="center" style="width: 700px; padding-left: 0.5rem; padding-right: 0.5rem; ">
                  <tr>
                      <td align="center">
                        <div style="font-size: 0.7rem;">
                          <p>
                            Puede leer nuestros términos y condiciones de Prepago en: https://pricelesscarrental.mx/Documents/ES/Terminos/Prepago.php
                          </p>
                          <p>
                            Puede leer nuestros términos y condiciones de Coberturas en: https://pricelesscarrental.mx/Documents/ES/Terminos/Coberturas.php
                          </p>
                          <p>
                            Puede leer nuestros términos y condiciones Generales en: https://pricelesscarrental.mx/Documents/ES/Terminos/General.php
                          </p>
                          <p>
                            Puede leer nuestro Aviso de privacidad en: https://pricelesscarrental.mx/Documents/ES/Aviso/Aviso_privacidad.php
                          </p>
                        </div>
                          <div style="font-size: 0.7rem;">
                              Priceless 2022.<br> © Todos los derechos reservados.
                          </div>
                      </td>
                  </tr>
              </table>
          </tr>
      </table>

      <script src="https://code.jquery.com/jquery-3.5.1.js" integrity="sha256-QWo7LDvxbWT2tbbQ97B53yJnYU3WhH/C8ycbRAkjPDc=" crossorigin="anonymous"></script>
  </body>
</html>
