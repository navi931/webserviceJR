<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \stdClass;
use \DateTime;
use Illuminate\Support\Facades\DB;
use \App\object_sorter;
use Response;
use \App\Calculos;
use \App\Conversiones;
use Mail;
use \App\Mail\enviarCorreo;
use \App\Mail\enviarCorreoCliente;
#409 error de token

class Control extends Controller
{
  public function getState(Request $request)
  {
    $request_viejo = $request->all();
    $request = Conversiones::checkArray($request);
    $diferencia = array_diff($request, $request_viejo);
    if([] != $diferencia)
    {
      return Response::json("Caracteres especiales no soportados",400);
    }
    if(isset($request['token']))
    {
      $token = $request['token'];

      $count = DB::select("SELECT COUNT(Token) as numero FROM dbo.[WebToken] WHERE Token = '$token' AND GETDATE() < FechaCaducidad");
      if(null != $count)
      {
        $count = $count[0]->numero;
        if($count == 1)
        {
          if(isset($request['IDReservacion']))
          {
            $id = $request['IDReservacion'];
            $data = DB::select("SELECT * FROM dbo.WebControl WHERE IDReservacion = '$id'");
            if(null == $data)
            {
              return Response::json('Codigo no encontrado',400);
            }
            else
            {
              $data = $data[0];
              return Response::json($data,201);
            }
          }
          else
          {
            //En este do lo que se hace es esque valida que el codigo de reservacion no este repetido, genera codigos hasta encontrar uno disponible
            do
            {
              $codigo = Conversiones::codeGenerator();
              $codigo = 'PR-'.$codigo;
              $numero_codigos = DB::select("SELECT COUNT(IDReservacion) as numero FROM dbo.[WebControl] WHERE IDReservacion = '$codigo'");
            }while($numero_codigos[0]->numero > 0);

            $insercion = DB::insert("SET DATEFORMAT MDY;
            INSERT INTO dbo.WebControl
            ([IDReservacion]
            ,[FechaCreacion]
            ,[FechaModificacion]
            ,[IDOficinaReservacion]
            ,[FechaReservacion]
            ,[IDOficinaRetorno]
            ,[FechaRetorno]
            ,[IDSippCodeSolicitado]
            ,[IDSippCodeSeleccionado]
            ,[IDAgencia]
            ,[IDComisionista]
            ,[IDCteLeal]
            ,[IDCodDescuento]
            ,[BanderaPrepago]
            ,[CDW]
            ,[DP]
            ,[PAI]
            ,[PLI]
            ,[PLIA]
            ,[MDW]
            ,[ERA]
            ,[ETS]
            ,[CA]
            ,[BS1]
            ,[BS2]
            ,[BS3]
            ,[CM]
            ,[GPS]
            ,[CargoPickup]
            ,[CargoDropOff]
            ,[LCRFee]
            ,[SCFee]
            ,[CCFee]
            ,[AirportFee]
            ,[HotelFee]
            ,[WebStatus])
            VALUES (
            '$codigo'
            ,getdate()
            ,getdate()
            ,''
            ,NULL
            ,''
            ,NULL
            ,''
            ,''
            ,''
            ,0
            ,''
            ,''
            ,0
            ,0
            ,0
            ,0
            ,0
            ,0
            ,0
            ,0
            ,0
            ,0
            ,0
            ,0
            ,0
            ,0
            ,0
            ,0
            ,0
            ,0
            ,0
            ,0
            ,0
            ,0
            ,0)");
            if(true == $insercion)
            {
              return Response::json($codigo,201);
            }
            else
            {
              return Response::json('Error al insertar control',500);
            }
          }
        }
      }
    }
    return Response::json('Token no valido',409);
  }
  public function setState(Request $request)
  {
    $request_viejo = $request->all();
    $request = Conversiones::checkArray($request);
    $diferencia = array_diff($request, $request_viejo);
    if([] != $diferencia)
    {
      return Response::json("Caracteres especiales no soportados",400);
    }
    if(isset($request['token']))
    {
      $token = $request['token'];

      $count = DB::select("SELECT COUNT(Token) as numero FROM dbo.[WebToken] WHERE Token = '$token' AND GETDATE() < FechaCaducidad");
      if(null != $count)
      {
        $count = $count[0]->numero;
        if($count == 1)
        {
          if(isset($request['IDReservacion']))
          {
            $id = $request['IDReservacion'];
            $update_string = "FechaModificacion = getdate()";

            if(isset($request['IDOficinaReservacion']))
            {
              $id_oficina = $request['IDOficinaReservacion'];
              $oficina = DB::select("SELECT Nombre,IDPlaza FROM dbo.WebOficina WHERE IDOficina = '$id_oficina'");
              if($oficina != null)
              {
                $oficina = $oficina[0];
                $valor = $oficina->Nombre;
                $update_string = $update_string.",NombreOficinaReservacion = '$valor'";

                $plaza = DB::select("SELECT Nombre FROM dbo.WebPlaza WHERE IDPlaza = '$oficina->IDPlaza'");
                if(null != $plaza)
                {
                  $plaza = $plaza[0];

                  $valor = $plaza->Nombre;
                  $update_string = $update_string.",NombrePlaza = '$valor'";

                  $valor = $oficina->IDPlaza;
                  $update_string = $update_string.",IDPlaza = '$valor'";
                }
              }

              $valor = $id_oficina;
              $update_string = $update_string.",IDOficinaReservacion = '$valor'";
            }

            if(isset($request['FechaReservacion']))
            {
              $valor = $request['FechaReservacion'];
              $update_string = $update_string.",FechaReservacion = '$valor'";
            }

            if(isset($request['IDOficinaRetorno']))
            {
              $id_oficina = $request['IDOficinaRetorno'];
              $oficina = DB::select("SELECT Nombre,IDPlaza FROM dbo.WebOficina WHERE IDOficina = '$id_oficina'");
              if($oficina != null)
              {
                $oficina = $oficina[0];
                $valor = $oficina->Nombre;
                $update_string = $update_string.",NombreOficinaRetorno = '$valor'";

                $plaza = DB::select("SELECT Nombre FROM dbo.WebPlaza WHERE IDPlaza = '$oficina->IDPlaza'");
                if(null != $plaza)
                {
                  $plaza = $plaza[0];

                  $valor = $plaza->Nombre;
                  $update_string = $update_string.",NombrePlazaRetorno = '$valor'";

                  $valor = $oficina->IDPlaza;
                  $update_string = $update_string.",IDPlazaRetorno = '$valor'";
                }
              }

              $valor = $id_oficina;
              $update_string = $update_string.",IDOficinaRetorno = '$valor'";
            }

            if(isset($request['FechaRetorno']))
            {
              $valor = $request['FechaRetorno'];
              $update_string = $update_string.",FechaRetorno = '$valor'";
            }

            if(isset($request['IDSippCodeSolicitado']))
            {
              $valor = $request['IDSippCodeSolicitado'];
              $update_string = $update_string.",IDSippCodeSolicitado = '$valor'";
            }

            if(isset($request['IDSippCodeSeleccionado']))
            {
              $valor = $request['IDSippCodeSeleccionado'];
              $update_string = $update_string.",IDSippCodeSeleccionado = '$valor'";
              $descripciones = DB::select("SELECT Descripcion,DescripcionUSA FROM dbo.[WebSIPPCode] WHERE IDSIPPCode = '$valor'");
              if($descripciones != null)
              {
                $descripciones = $descripciones[0];
                $valor = $descripciones->Descripcion;
                $update_string = $update_string.",SippCodeDescripcion = '$valor'";

                $valor = $descripciones->DescripcionUSA;
                $update_string = $update_string.",SippCodeDescripcionUSA = '$valor'";
              }
              else
              {
                $valor = 'No se encontro descripcion';
                $update_string = $update_string.",SippCodeDescripcion = '$valor'";

                $valor = 'No se encontro descripcion';
                $update_string = $update_string.",SippCodeDescripcionUSA = '$valor'";
              }
            }

            if(isset($request['IDAgencia']))
            {
              $valor = $request['IDAgencia'];
              $update_string = $update_string.",IDAgencia = '$valor'";
            }
            if(isset($request['IDComisionista']))
            {
              $valor = $request['IDComisionista'];
              $update_string = $update_string.",IDComisionista = '$valor'";
            }

            if(isset($request['IDCteLeal']))
            {
              $valor = $request['IDCteLeal'];
              $update_string = $update_string.",IDCteLeal = '$valor'";
            }

            if(isset($request['IDCodDescuento']))
            {
              $valor = $request['IDCodDescuento'];
              $update_string = $update_string.",IDCodDescuento = '$valor'";
            }

            if(isset($request['BanderaPrepago']))
            {
              $valor = $request['BanderaPrepago'];
              $update_string = $update_string.",BanderaPrepago = '$valor'";
            }

            if(isset($request['CDW']))
            {
              $valor = $request['CDW'];
              $update_string = $update_string.",CDW = '$valor'";
            }

            if(isset($request['DP']))
            {
              $valor = $request['DP'];
              $update_string = $update_string.",DP = '$valor'";
            }

            if(isset($request['Edad']))
            {
              $valor = $request['Edad'];
              $update_string = $update_string.",Edad = '$valor'";
            }

            if(isset($request['PAI']))
            {
              $valor = $request['PAI'];
              $update_string = $update_string.",PAI = '$valor'";
            }

            if(isset($request['PLI']))
            {
              $valor = $request['PLI'];
              $update_string = $update_string.",PLI = '$valor'";
            }

            if(isset($request['PLIA']))
            {
              $valor = $request['PLIA'];
              $update_string = $update_string.",PLIA = '$valor'";
            }

            if(isset($request['MDW']))
            {
              $valor = $request['MDW'];
              $update_string = $update_string.",MDW = '$valor'";
            }

            if(isset($request['ERA']))
            {
              $valor = $request['ERA'];
              $update_string = $update_string.",ERA = '$valor'";
            }

            if(isset($request['ETS']))
            {
              $valor = $request['ETS'];
              $update_string = $update_string.",ETS = '$valor'";
            }

            if(isset($request['CA']))
            {
              $valor = $request['CA'];
              $update_string = $update_string.",CA = '$valor'";
            }

            if(isset($request['BS1']))
            {
              $valor = $request['BS1'];
              $update_string = $update_string.",BS1 = '$valor'";
            }

            if(isset($request['BS2']))
            {
              $valor = $request['BS2'];
              $update_string = $update_string.",BS2 = '$valor'";
            }

            if(isset($request['BS3']))
            {
              $valor = $request['BS3'];
              $update_string = $update_string.",BS3 = '$valor'";
            }

            if(isset($request['CM']))
            {
              $valor = $request['CM'];
              $update_string = $update_string.",CM = '$valor'";
            }

            if(isset($request['GPS']))
            {
              $valor = $request['GPS'];
              $update_string = $update_string.",GPS = '$valor'";
            }

            if(isset($request['CargoPickup']))
            {
              $valor = $request['CargoPickup'];
              $update_string = $update_string.",CargoPickup = '$valor'";
            }

            if(isset($request['CargoDropOff']))
            {
              $valor = $request['CargoDropOff'];
              $update_string = $update_string.",CargoDropOff = '$valor'";
            }

            if(isset($request['LCRFee']))
            {
              $valor = $request['LCRFee'];
              $update_string = $update_string.",LCRFee = '$valor'";
            }
            if(isset($request['SCFee']))
            {
              $valor = $request['SCFee'];
              $update_string = $update_string.",SCFee = '$valor'";
            }

            if(isset($request['CCFee']))
            {
              $valor = $request['CCFee'];
              $update_string = $update_string.",CCFee = '$valor'";
            }

            if(isset($request['AirportFee']))
            {
              $valor = $request['AirportFee'];
              $update_string = $update_string.",AirportFee = '$valor'";
            }
            if(isset($request['HotelFee']))
            {
              $valor = $request['HotelFee'];
              $update_string = $update_string.",HotelFee = '$valor'";
            }

            if(isset($request['WebStatus']))
            {
              $valor = $request['WebStatus'];
              $update_string = $update_string.",WebStatus = '$valor'";
            }

            $update = DB::update("SET DATEFORMAT YMD; UPDATE dbo.WebControl SET $update_string WHERE IDReservacion = '$id'");
            $control = DB::select("SELECT * FROM dbo.WebControl WHERE IDReservacion = '$id'");
            if($control != null)
            {
              $control = $control[0];
              return Response::json($control,201);
            }
            else
            {
              return Response::json('No se ha encontrado el id, id no valido',400);
            }
          }
          else
          {
            return Response::json('No se ha encontrado el id en el request',400);
          }
        }
      }
    }
    return Response::json('Token no valido',409);
  }
  public function getPersonas(Request $request)
  {
    $request_viejo = $request->all();
    $request = Conversiones::checkArray($request);
    $diferencia = array_diff($request, $request_viejo);
    if([] != $diferencia)
    {
      return Response::json("Caracteres especiales no soportados",400);
    }
    if(isset($request['token']))
    {
      $token = $request['token'];

      $count = DB::select("SELECT COUNT(Token) as numero FROM dbo.[WebToken] WHERE Token = '$token' AND GETDATE() < FechaCaducidad");
      if(null != $count)
      {
        $count = $count[0]->numero;
        if($count == 1)
        {
          if(isset($request['IDReservacion']))
          {
            if(isset($request['minutos']))
            {
              $minutos = $request['minutos'];
            }
            else
            {
              $minutos = 5;
            }
            $id = $request['IDReservacion'];
            //Aqui estamos contando todas las sesiones con tantos minutos de retraso como se nos pidio
            $sesiones = DB::select("SELECT COUNT(IDReservacion) as personas FROM dbo.WebControl WHERE FechaModificacion BETWEEN DATEADD(minute, -$minutos, GETDATE()) AND GETDATE()
                                    AND IDPlaza = (SELECT IDPlaza FROM dbo.WebControl WHERE IDReservacion = '$id')");
            if(null == $sesiones)
            {
              return Response::json('No se encontraron sesiones',500);
            }
            $sesiones_totales = $sesiones[0]->personas;

            //Aqui estamos contando todas las sesiones con tantos minutos de retraso como se nos pidio y si que alguna Fecha de Reservacion o de retorno coincidan
            $sesiones = DB::select("SELECT COUNT(IDReservacion) as personas FROM dbo.WebControl WHERE FechaModificacion BETWEEN DATEADD(minute, -$minutos, GETDATE()) AND GETDATE()
                                    AND IDPlaza = (SELECT IDPlaza FROM dbo.WebControl WHERE IDReservacion = '$id')
                                    AND (FechaReservacion BETWEEN (SELECT FechaReservacion FROM dbo.WebControl WHERE IDReservacion = '$id') AND (SELECT FechaRetorno FROM dbo.WebControl WHERE IDReservacion = '$id')
                                    OR FechaRetorno BETWEEN (SELECT FechaReservacion FROM dbo.WebControl WHERE IDReservacion = '$id') AND (SELECT FechaRetorno FROM dbo.WebControl WHERE IDReservacion = '$id'))");
            if(null == $sesiones)
            {
              return Response::json('No se encontraron sesiones',500);
            }
            $sesiones_totales_fechas = $sesiones[0]->personas;

            //Aqui estamos contando todas las sesiones con tantos minutos de retraso como se nos pidio y con el mismo IDSippCodeSeleccionado aparte que alguna Fecha de Reservacion o de retorno coincidan
            $sesiones = DB::select("SELECT COUNT(IDReservacion) as personas FROM dbo.WebControl WHERE FechaModificacion BETWEEN DATEADD(minute, -$minutos, GETDATE()) AND GETDATE()
                                    AND IDSippCodeSeleccionado = (SELECT IDSippCodeSeleccionado FROM dbo.WebControl WHERE IDReservacion = '$id')
                                    AND IDPlaza = (SELECT IDPlaza FROM dbo.WebControl WHERE IDReservacion = '$id')
                                    AND (FechaReservacion BETWEEN (SELECT FechaReservacion FROM dbo.WebControl WHERE IDReservacion = '$id') AND (SELECT FechaRetorno FROM dbo.WebControl WHERE IDReservacion = '$id')
                                    OR FechaRetorno BETWEEN (SELECT FechaReservacion FROM dbo.WebControl WHERE IDReservacion = '$id') AND (SELECT FechaRetorno FROM dbo.WebControl WHERE IDReservacion = '$id'))");
            if(null == $sesiones)
            {
              return Response::json('No se encontraron sesiones',500);
            }
            $sesiones_totales_sippcode = $sesiones[0]->personas;

            //Encapsulamos todo en unn objeto, y regresamos el objeto
            $sesiones = new stdClass();
            $sesiones->total_sesiones = $sesiones_totales;
            $sesiones->sesiones_totales_fechas = $sesiones_totales_fechas;
            $sesiones->sesiones_totales_sippcode = $sesiones_totales_sippcode;

            return Response::json($sesiones,200);
          }
          else
          {
              return Response::json('No se encontro IDReservacion',400);
          }
        }
      }
    }
    return Response::json('Token no valido',409);
  }
  public function getToken(Request $request)
  {
    $request_viejo = $request->all();
    $request = Conversiones::checkArray($request);
    $diferencia = array_diff($request, $request_viejo);
    if([] != $diferencia)
    {
      return Response::json("Caracteres especiales no soportados",400);
    }
    if(isset($request['key']))
    {
      $key = $request['key'];
      if(isset($request['minutes']))
      {
        $minutes = intval($request['minutes']);
      }
      else
      {
        $minutes = 5;
      }
      $count = DB::select("SELECT COUNT(KeyWS) as numero FROM dbo.EmpresaWS WHERE KeyWS = '$key' AND status = 'Activo'");
      if(null != $count)
      {
        $count = $count[0]->numero;
        if($count == 1)
        {
          $count = 1;
          while ($count >= 1)
          {
            $token = Conversiones::tokenGenerator();

            $count = DB::select("SELECT COUNT(Token) as numero FROM dbo.[WebToken] WHERE Token = '$token' AND GETDATE() < FechaCaducidad");
            $count = $count[0]->numero;
          }

          $insercion = DB::insert("SET DATEFORMAT YMD;
          INSERT INTO dbo.WebToken ([keyWS],[Token],[FechaCaducidad]) VALUES ('$key','$token',DATEADD(minute, $minutes, GETDATE()));");
          if($insercion)
          {
            return Response::json($token,200);
          }
          else
          {
            return Response::json('Error al validar token',500);
          }
        }
      }
    }
    return Response::json('Error, key no valida',400);
  }
  public function enviarCorreo(Request $request)
  {
    //Dejamos que dure hasta 50 min
    ini_set('max_execution_time', 3000);
    //Cachamos variables forzosas
    $codigo = $request['codigo'];
    $apellido = $request['apellido'];

    $reserva = DB::select("SELECT  WebSIPPCode.Descripcion,WebReservas.*
                          FROM WebReservas,WebSIPPCode WHERE WebReservas.IDReservacion = '$codigo' AND WebReservas.ApellidoPaterno = '$apellido' AND WebSIPPCode.IDSIPPCode = WebReservas.IDSIPPCode");
    if(null == $reserva)
    {
      return Response::json('reserva no encontrada',500);
    }

    //Formamos el objeto de reserva

    $reserva = $reserva[0];

    //Nombre de la agencia
    $nombre_agencia = DB::select("SELECT  RazonSocial
                          FROM WebAgencia WHERE IDAgencia = '$reserva->IDAgencia'");
    if(null != $nombre_agencia)
    {
      $nombre_agencia = $nombre_agencia[0]->RazonSocial;
    }
    else
    {
      $nombre_agencia = '';
    }

    //Nombre del comisionista
    $nombre_comisionista = DB::select("SELECT NombreComisionista
                          FROM WebComisionista WHERE IDComisionista = '$reserva->IDComisionista'");
    if(null != $nombre_comisionista)
    {
      $nombre_comisionista = $nombre_comisionista[0]->NombreComisionista;
    }
    else
    {
      $nombre_comisionista = '';
    }


    $data = new stdClass();

    $data->CantidadHora = $reserva->CantidadHora;

    if($data->CantidadHora > 0)
    {
      $dias_seguros = $reserva->DiasRenta + 1;
    }
    else
    {
      $dias_seguros = $reserva->DiasRenta;
    }

    switch ($reserva->TipoMovRegistro) {
      case 'A':
        $data->TipoMovRegistro = 'Active';
        break;
      case 'C':
        $data->TipoMovRegistro = 'Cancelada';
        break;
      default:
        $data->TipoMovRegistro = 'No Activa';
        break;
    }
    $data->IDTarifa = $reserva->IDTarifa;
    $data->autorizacion_Paypal = $reserva->TC_Aut;
    $data->IDComisionista = $reserva->IDComisionista;
    $data->IDCteLeal = $reserva->IDCteLeal;
    $data->Email = $reserva->Email;
    $data->Telefono = $reserva->Telefono;

    $data->Descripcion = $reserva->Descripcion;
    $data->IDCodDescuento = $reserva->IDCodDescuento;
    $data->IDAgencia = $reserva->IDAgencia;
    $data->BanderaPrepago = $reserva->BanderaPrepago;
    $data->CantidadMes = $reserva->CantidadMes;
    $data->CantidadSemana = $reserva->CantidadSemana;
    $data->CantidadDiaExtra = $reserva->CantidadDiaExtra;
    $data->CantidadDiaFinSemana = $reserva->CantidadDiaFinSemana;
    $data->CantidadDia = $reserva->CantidadDia;
    $data->TarifaMesTK = $reserva->TarifaMesTK;
    $data->TarifaSemanaTK = $reserva->TarifaSemanaTK;
    $data->TarifaDiaExtraTK = $reserva->TarifaDiaExtraTK;
    $data->TarifaDiaTK = $reserva->TarifaDiaTK;
    $data->TarifaFinSemanaTK = $reserva->TarifaFinSemanaTK;
    $data->TarifaHoraTK = $reserva->TarifaHoraTK;
    $data->ImporteMesTK = $reserva->ImporteMesTK;
    $data->ImporteSemanaTK = $reserva->ImporteSemanaTK;
    $data->ImporteDiaExtraTK = $reserva->ImporteDiaExtraTK;
    $data->ImporteDiaTK = $reserva->ImporteDiaTK;
    $data->ImporteDiaFinSemanaTK = $reserva->ImporteDiaFinSemanaTK;
    $data->ImporteHoraTK = $reserva->ImporteHoraTK;
    $data->nombre_agencia = $nombre_agencia;
    $data->nombre_comisionista = $nombre_comisionista;
    $data->Edad = $reserva->Edad;
    $data->IDReservacion = $reserva->IDReservacion;
    $data->IDSIPPCode = $reserva->IDSIPPCode;
    $data->FechaHoraOperacion = $reserva->FechaHoraOperacion;
    $data->FechaControl = $reserva->FechaControl;
    $data->ApellidoPaterno = $reserva->ApellidoPaterno;
    $data->ApellidoMaterno = $reserva->ApellidoMaterno;
    $data->Nombre = $reserva->Nombre;
    $data->NombreCompleto = $reserva->NombreCompleto;
    $data->Moneda = $reserva->Moneda;
    $data->IDOficinaReservacion = $reserva->IDOficinaReservacion;
    $data->FechaReservacion = $reserva->FechaReservacion;
    $data->IDOficinaRetorno = $reserva->IDOficinaRetorno;
    $data->FechaRetorno = $reserva->FechaRetorno;
    $data->DiasRenta = $reserva->DiasRenta;
    $data->DiasSeguros = $dias_seguros;
    $data->TotalTK = $reserva->TotalTK;
    $data->TotalCoberturas = $reserva->TotalCoberturas;
    $data->TotalExtras = $reserva->TotalExtras;
    $data->Subtotal = $reserva->Subtotal;
    $data->BanderaIVA = $reserva->BanderaIVA;
    $data->PorIva = $reserva->PorIva;
    $data->MontoIva = $reserva->MontoIva;
    $data->Total = $reserva->Total;
    $data->Moneda = $reserva->Moneda;


    //Datos Oficina Salida
    $salida = DB::select("SELECT Nombre, Calle, Telefono1,Telefono2 FROM WebOficina WHERE IDOficina = '$data->IDOficinaReservacion'");
    if(null == $salida)
    {
      return Response::json("Oficina de salida no encontrada",400);
    }
    $data->salida = $salida[0];
    //Datos Oficina regreso
    $regreso = DB::select("SELECT Nombre, Calle, Telefono1,Telefono2 FROM WebOficina WHERE IDOficina = '$data->IDOficinaRetorno'");
    if(null == $regreso)
    {
      return Response::json("Oficina de regreso no encontrada",400);
    }
    $data->regreso = $regreso[0];


    //Obtener el email de la plaza
    $email_plaza = DB::select("SELECT Email FROM WebPlaza WHERE IDPlaza in (SELECT IDPlazaReservacion FROM WebReservas WHERE IDReservacion = '$data->IDReservacion')");
    if(null == $email_plaza)
    {
      return Response::json("Oficina de regreso no encontrada",400);
    }
    $email_plaza = $email_plaza[0]->Email;

    //Obtener el email general
    $email_general = DB::select("SELECT Email FROM WebConfiguracionGral");
    if(null == $email_general)
    {
      return Response::json("Oficina de regreso no encontrada",400);
    }
    $email_general = $email_general[0]->Email;

    //Meter el correo del cliente
    $array_clientes = [];
    array_push($array_clientes,$data->Email);
    array_push($array_clientes,$email_plaza);
    array_push($array_clientes,$email_general);
    array_push($array_clientes,env('EMAIL_PROPIO'));

    //Validar correo por correo
    $array_clientes_validado = [];
    foreach ($array_clientes as $email)
    {
      if('' == $email || null == $email)
      {
        continue;
      }
      //Datos para verificar el correo
      $options = array(
              'ssl'=>array(
                'verify_peer' => false,
                'verify_peer_name' => false,
              ),
            );
            $context = stream_context_create($options);
      $validation = file_get_contents("https://api.debounce.io/v1/?api=5bacdd45e74d0&email=$email", false, $context);
      $validation = json_decode($validation);

      //Correo inválido
      if("1" == "1")
      {
          array_push($array_clientes_validado,$email);
      }
    }


    //Enviar correo a los emails validados
    Mail::to($array_clientes_validado)->send(new enviarCorreoCliente($data));

    //Enviar correo a la plaza
    $array_corporativo_validado = [];

    //Datos para verificar el correo
    $options = array(
            'ssl'=>array(
              'verify_peer' => false,
              'verify_peer_name' => false,
            ),
          );
          $context = stream_context_create($options);

    if('' != $email_plaza && null != $email_plaza)
    {
      $validation = file_get_contents("https://api.debounce.io/v1/?api=5bacdd45e74d0&email=$email_plaza", false, $context);
      $validation = json_decode($validation);

      //Correo inválido
      if("1" == "1")
      {
          array_push($array_corporativo_validado,$email_plaza);
      }
    }


    // //de mientras Enviar a cmay
    // $email_temporal = "agonzalez@pricelesscarrental.com";
    //
    // //Datos para verificar el correo
    // $options = array(
    //         'ssl'=>array(
    //           'verify_peer' => false,
    //           'verify_peer_name' => false,
    //         ),
    //       );
    //       $context = stream_context_create($options);
    // $validation = file_get_contents("https://api.debounce.io/v1/?api=5bacdd45e74d0&email=$email_temporal", false, $context);
    // $validation = json_decode($validation);
    //
    // //Correo inválido
    // if("1" == "1")
    // {
    //     array_push($array_corporativo_validado,$email_temporal);
    // }



    //Enviar correo a los emails validados
    Mail::to($array_corporativo_validado)->send(new enviarCorreo($data));

    return Response::json(true,200);
  }
}
