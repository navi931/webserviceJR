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
use \App\Validaciones;
use Mail;
use \App\Mail\enviarCorreo;
use Stripe\Stripe;
use Stripe\PaymentIntent;

/*  Codigos de error
*
* 1.- No se encontro precios de seguros del PCS 500
* 2.- No se encontro Plan code seguros 500
*
*/

class Page4Controller extends Controller
{
  //Optimizado
  function getPrecioReal(Request $request)
  {
    //Obtenemos el request y checamos si tiene algun Caracter
    //de algun slq injection
    $request_viejo = $request->all();
    $request = Conversiones::checkArray($request);
    $diferencia = array_diff($request, $request_viejo);
    if([] != $diferencia)
    {
      return Response::json("Caracteres especiales no soportados",400);
    }

    //Revisar si hay token
    if(!isset($request['token']))
    {
      return Response::json('Token no enviado',400);
    }

    $token = $request['token'];

    //Buscar token
    $count = DB::select("SELECT COUNT(Token) as numero FROM dbo.[WebToken] WHERE Token = '$token' AND GETDATE() < FechaCaducidad");

    $count = $count[0]->numero;

    //Validar token
    if($count < 1)
    {
      return Response::json('Token no valido',400);
    }
    //Verificamos que los parametros se mandaron correctamente
    if(!(isset($request['fecha_inicial']) && isset($request['fecha_final']) && isset($request['id']) && isset($request['id_retorno']) && isset($request['SIPPCode'])))
    {
      return Response::json('Parametros no mandados correctamente',400);
    }

    //Obtenemos los parametros
    if(isset($request['Edad']))
    {
      $edad = $request['Edad'];
    }
    else
    {
      $edad = 18;
    }

    if(isset($request['id_cliente']))
    {
      $id_cliente = $request['id_cliente'];

      $usuario_validado = Validaciones::validarUsuario($id_cliente,null);

      if(false == $usuario_validado)
      {
          $id_cliente = '0';
      }
    }
    else
    {
      $id_cliente = '0';
    }

    if(isset($request['id_agencia']))
    {
      $id_agencia = $request['id_agencia'];
      if(!isset($request['password']))
      {
        $id_agencia = '0';
      }
      else
      {
        $password = $request['password'];
        $usuario_validado = Validaciones::validarUsuario($id_agencia,$password);

        if(false == $usuario_validado)
        {
            $id_agencia = '0';
        }
      }
    }
    else
    {
      $id_agencia = '0';
    }

    if(isset($request['id_comisionista']))
    {
      $id_comisionista = $request['id_comisionista'];

      if(!isset($request['password']))
      {
        $id_comisionista = '0';
      }
      else
      {
        $password = $request['password'];
        $usuario_validado = Validaciones::validarUsuario($id_comisionista,$password);

        if(false == $usuario_validado)
        {
            $id_comisionista = '0';
        }
      }
    }
    else
    {
      $id_comisionista = '0';
    }

    if(isset($request['sub_tarifa']))
    {
      $sub_tarifa = $request['sub_tarifa'];
    }
    else
    {
      $sub_tarifa = '0';
    }

    if(isset($request['id_promocion']))
    {
      $id_promocion = $request['id_promocion'];
    }
    else
    {
      $id_promocion = '0';
    }

    //Seguros
    if(isset($request['DP']))
    {
      $dp = $request['DP'];
    }
    else
    {
      $dp = 0;
    }

    if(isset($request['CDW']))
    {
      $cdw = $request['CDW'];
    }
    else
    {
      $cdw = 0;
    }

    if(isset($request['PAI']))
    {
      $pai = $request['PAI'];
    }
    else
    {
      $pai = 0;
    }

    if(isset($request['PLI']))
    {
      $pli = $request['PLI'];
    }
    else
    {
      $pli = 0;
    }

    if(isset($request['PLIA']))
    {
      $plia = $request['PLIA'];
    }
    else
    {
      $plia = 0;
    }

    if(isset($request['MDW']))
    {
      $mdw = $request['MDW'];
    }
    else
    {
      $mdw = 0;
    }

    if(isset($request['ERA']))
    {
      $era = $request['ERA'];
    }
    else
    {
      $era = 0;
    }

    if(isset($request['ETS']))
    {
      $ets = $request['ETS'];
    }
    else
    {
      $ets = 0;
    }

    if(isset($request['CA']))
    {
      $ca = $request['CA'];
    }
    else
    {
      $ca = 0;
    }

    if(isset($request['BS1']))
    {
      $bs1 = $request['BS1'];
    }
    else
    {
      $bs1 = 0;
    }

    if(isset($request['BS2']))
    {
      $bs2 = $request['BS2'];
    }
    else
    {
      $bs2 = 0;
    }

    if(isset($request['BS3']))
    {
      $bs3 = $request['BS3'];
    }
    else
    {
      $bs3 = 0;
    }

    if(isset($request['CM']))
    {
      $cm = $request['CM'];
    }
    else
    {
      $cm = 0;
    }

    if(isset($request['GPS']))
    {
      $gps = $request['GPS'];
    }
    else
    {
      $gps = 0;
    }

    if(isset($request['Prepago']))
    {
      $prepago = $request['Prepago'];
    }
    else
    {
      $prepago = 0;
    }

    $sipp_code = $request['SIPPCode'];
    $id_oficina = $request['id'];
    $id_oficina_retorno = $request['id_retorno'];

    $seguros = ['DP'=> $dp,'CDW'=> $cdw,'PAI'=> $pai,'PLI'=> $pli,'PLIA'=> $plia,'Prepago'=>$prepago,
    'MDW'=> $mdw,'ERA'=> $era,'ETS'=> $ets,'CA'=> $ca,'BS1'=> $bs1,'BS2'=> $bs2,'BS3'=> $bs3,
    'CM'=> $cm,'GPS'=> $gps,'LCRF'=>1,'AF'=>1,'HF'=>1,'SCF'=>1];

    // Validamos edad y si es menor a la menor establecido, no procede, en caso contrario si procede
    // Para efectos practicos si se sobre pasa de la edad la seteamos a la edad mas grande que tenemos definida, sin embargo guardamos la edad real

    //Buscamos los limites de edad
    $edad_menor = DB::select("SELECT Edad FROM dbo.WebEdad ORDER BY Edad ASC");
    $edad_mayor = DB::select("SELECT Edad FROM dbo.WebEdad ORDER BY Edad DESC");
    if(null == $edad_menor or null == $edad_mayor)
    {
      return Response::json('No se encontro la tabla de edades',500);
    }

    $edad_menor = $edad_menor[0]->Edad;
    $edad_mayor = $edad_mayor[0]->Edad;

    $edad_real = $edad;

    //Validamos la edad
    if($edad < $edad_menor)
    {
      return Response::json('Edad Invalida',400);
    }

    if($edad > $edad_mayor)
    {
      $edad = $edad_mayor;
    }

    //Encontramos los datos de las oficinas con el id_oficina y Id_oficina_retorno
    $oficina = DB::select("SELECT IDPlaza,IDTarifa,PorIVA FROM dbo.[WebOficina] WHERE IDOficina = '$id_oficina'");
    $oficina_regreso = DB::select("SELECT IDPlaza FROM dbo.[WebOficina] WHERE IDOficina = '$id_oficina_retorno'");

    if(null == $oficina || null == $oficina_regreso)
    {
      return Response::json('Oficina no encontrada', 500);
    }

    //Obtenemos datos de la oficina
    $oficina = $oficina[0];
    $id_plaza = $oficina->IDPlaza;
    $iva = 1 + ($oficina->PorIVA/100);

    //Obtenemos la plaza de la oficina de retorno
    $id_plaza_regreso = $oficina_regreso[0]->IDPlaza;

    //Buscamos la configuracion general de la empresa
    $configuracion = DB::select("SELECT DiasXMes, DiasXSem, HorasxDia,PorDescuentoPpgo FROM dbo.WebConfiguracionGral");
    if($configuracion == null)
    {
      return Response::json('Configuracion no encontrada', 500);
    }
    $configuracion = $configuracion[0];

    //Buscamos el auto para hacer la cotizacion
    $grupo_auto = DB::select("SELECT b.Descripcion,a.IDSIPPCode1 FROM dbo.[WebGrupoAutos] a LEFT JOIN dbo.[WebSIPPCode] b ON  a.IDSIPPCode1 = b.IDSIPPCode
                                      WHERE a.IDPlaza = $id_plaza AND a.Activo = 1 AND a.IDSIPPCode1 = '$sipp_code'  ORDER BY b.Ordenamiento");
    if($grupo_auto == null)
    {
      return Response::json('Carro no encontrado', 500);
    }
    $grupo_auto = $grupo_auto[0];

    $tiempo  = Conversiones::getDaysHours($request['fecha_inicial'],$request['fecha_final']);
    if(gettype($tiempo) == 'integer')
    {
      return Response::json('Fechas Invalidas',400);
    }
    $fecha = $tiempo->fecha_inicial;
    $es_fin_semana = $tiempo->es_fin_semana;
    $tarifas_filtradas =  Calculos::getTarifas($fecha,$id_promocion,$id_cliente,$id_agencia,$id_comisionista,$id_oficina,$id_plaza,$sub_tarifa);          //Obtenemos las Tarifas e la fecha y con el IDTarifa obtenido


    //Le colocamos su descripcion y SIPPCode
    $auto = new stdClass();
    $auto->descripcion = $grupo_auto->Descripcion;
    $auto->SIPPCode = $grupo_auto->IDSIPPCode1;

    //Validar Promocion
    $promocion = Validaciones::validarCodigoDescuento($id_promocion,$tiempo->fecha_inicial,$tiempo->fecha_final,$tiempo->dias,$id_plaza,$id_oficina,$fecha,$auto->SIPPCode,$id_agencia,$id_comisionista);;

    //Obtener cotizacion del auto
    $auto = Calculos::getPrecio($id_cliente,$id_agencia,$id_comisionista,$id_oficina,$id_plaza,$id_plaza_regreso,$es_fin_semana,$configuracion,$promocion,$iva,$edad,$fecha,$tiempo,$tarifas_filtradas,$auto,$seguros);

    return Response::json($auto,201);
  }

  //Optimizado
  public function insertarReserva(Request $request)
  {
    //Obtenemos el request y checamos si tiene algun Caracter
    //de algun slq injection
    $request_viejo = $request->all();
    $request = Conversiones::checkArray($request);
    $diferencia = array_diff($request, $request_viejo);
    if([] != $diferencia)
    {
      return Response::json("Caracteres especiales no soportados",400);
    }

    //Revisar si hay token
    if(!isset($request['token']))
    {
      return Response::json('Token no enviado',400);
    }

    $token = $request['token'];

    //Buscar token
    $count = DB::select("SELECT COUNT(Token) as numero FROM dbo.[WebToken] WHERE Token = '$token' AND GETDATE() < FechaCaducidad");

    $count = $count[0]->numero;

    //Validar token
    if($count < 1)
    {
      return Response::json('Token no valido',400);
    }

    //Buscar key de la empresa
    $key = DB::select("SELECT keyWS FROM dbo.[WebToken] WHERE Token = '$token'");

    //Validar key
    if(null == $key)
    {
      return Response::json('Token no valido',400);
    }

    //Codificar llave
    $key = $key[0]->keyWS;
    $key = Conversiones::codificar($key);

    //Verificamos que los parametros se mandaron correctamente
    if(!(isset($request['fecha_inicial']) && isset($request['fecha_final']) && isset($request['id']) && isset($request['id_retorno']) && isset($request['SIPPCode'])
    && isset($request['nombre']) && isset($request['paterno']) && isset($request['email'])&& isset($request['codigo'])
    && isset($request['Prepago'])))
    {
      return Response::json('Parametros no mandados correctamente',400);
    }

    if(isset($request['Edad']))
    {
       $edad = $request['Edad'];
    }
    else
    {
         $edad = 18;
    }

    if(isset($request['materno']))
    {
       $materno = $request['materno'];
    }
    else
    {
       $materno = '';
    }

    if(isset($request['id_cliente']))
    {
       $id_cliente = $request['id_cliente'];

       $usuario_validado = Validaciones::validarUsuario($id_cliente,null);

       if(false == $usuario_validado)
       {
           $id_cliente = '0';
       }
     }
    else
    {
       $id_cliente = '0';
     }

    if(isset($request['id_agencia']))
    {
       $id_agencia = $request['id_agencia'];
       if(!isset($request['password']))
       {
         $id_agencia = '0';
       }
       else
       {
         $password = $request['password'];
         $usuario_validado = Validaciones::validarUsuario($id_agencia,$password);

         if(false == $usuario_validado)
         {
             $id_agencia = '0';
         }
       }
     }
    else
    {
       $id_agencia = '0';
     }

    if(isset($request['id_comisionista']))
    {
       $id_comisionista = $request['id_comisionista'];

       if(!isset($request['password']))
       {
         $id_comisionista = '0';
       }
       else
       {
         $password = $request['password'];
         $usuario_validado = Validaciones::validarUsuario($id_comisionista,$password);

         if(false == $usuario_validado)
         {
             $id_comisionista = '0';
         }
       }
     }
    else
    {
       $id_comisionista = '0';
     }

    if(isset($request['sub_tarifa']))
    {
       $sub_tarifa = $request['sub_tarifa'];
     }
    else
    {
       $sub_tarifa = '0';
     }

    if(isset($request['id_promocion']))
    {
        $id_promocion = $request['id_promocion'];
      }
    else
    {
        $id_promocion = '0';
      }

    //Seguros
    if(isset($request['DP']))
    {
        $dp = $request['DP'];
      }
    else
    {
        $dp = 0;
      }

    if(isset($request['CDW']))
    {
        $cdw = $request['CDW'];
      }
    else
    {
        $cdw = 0;
      }

    if(isset($request['PAI']))
    {
        $pai = $request['PAI'];
      }
    else
    {
        $pai = 0;
      }

    if(isset($request['PLI']))
    {
        $pli = $request['PLI'];
      }
    else
    {
        $pli = 0;
      }

    if(isset($request['PLIA']))
    {
      $plia = $request['PLIA'];
    }
    else
    {
      $plia = 0;
    }

    if(isset($request['MDW']))
    {
      $mdw = $request['MDW'];
    }
    else
    {
      $mdw = 0;
    }

    if(isset($request['ERA']))
    {
      $era = $request['ERA'];
    }
    else
    {
      $era = 0;
    }

    if(isset($request['ETS']))
    {
      $ets = $request['ETS'];
    }
    else
    {
      $ets = 0;
    }

    if(isset($request['CA']))
    {
      $ca = $request['CA'];
    }
    else
    {
      $ca = 0;
    }

    if(isset($request['BS1']))
    {
      $bs1 = $request['BS1'];
    }
    else
    {
      $bs1 = 0;
    }

    if(isset($request['BS2']))
    {
      $bs2 = $request['BS2'];
    }
    else
    {
      $bs2 = 0;
    }

    if(isset($request['BS3']))
    {
      $bs3 = $request['BS3'];
    }
    else
    {
      $bs3 = 0;
    }

    if(isset($request['CM']))
    {
      $cm = $request['CM'];
    }
    else
    {
      $cm = 0;
    }

    if(isset($request['GPS']))
    {
      $gps = $request['GPS'];
    }
    else
    {
      $gps = 0;
    }

    if(isset($request['telefono']))
    {
      $telefono = $request['telefono'];
    }
    else
    {
      $telefono = 0;
    }

    if(isset($request['licencia']))
    {
      $licencia = $request['licencia'];
    }
    else
    {
      $licencia = NULL;
    }

    if(isset($request['licencia_expira']))
    {
      $licencia_expira = $request['licencia_expira'];
    }
    else
    {
      $licencia_expira = NULL;
    }

    if(isset($request['licencia_estado']))
    {
      $licencia_estado = $request['licencia_estado'];
    }
    else
    {
      $licencia_estado = NULL;
    }

    if(isset($request['aerolinea']))
    {
      $aerolinea = $request['aerolinea'];
    }
    else
    {
      $aerolinea = NULL;
    }

    if(isset($request['vuelo']))
    {
      $vuelo = $request['vuelo'];
    }
    else
    {
      $vuelo = NULL;
    }

    //Obtener parametros obligatorios
    $prepago = $request['Prepago'];
    $sipp_code = $request['SIPPCode'];
    $id_oficina = $request['id'];
    $id_oficina_retorno = $request['id_retorno'];
    $seguros = ['DP'=> $dp,'CDW'=> $cdw,'PAI'=> $pai,'PLI'=> $pli,'PLIA'=> $plia,'Prepago'=>$prepago,
    'MDW'=> $mdw,'ERA'=> $era,'ETS'=> $ets,'CA'=> $ca,'BS1'=> $bs1,'BS2'=> $bs2,'BS3'=> $bs3,
    'CM'=> $cm,'GPS'=> $gps,'LCRF'=>1,'AF'=>1,'HF'=>1,'SCF'=>1];

    $nombre = $request['nombre'];
    $paterno = $request['paterno'];
    $nombre_completo = $nombre.' '.$paterno.' '.$materno;
    $email = $request['email'];
    $codigo = $request['codigo'];

    //Verificaremos si el codigo ya ha sido insertado en Temporales
    $reserva_insertada = DB::select("SELECT IDReservacion FROM dbo.WebReservasTMP WHERE IDReservacion = '$codigo'");
    if(null != $reserva_insertada)
    {
      return Response::json('El codigo de reservacion ya ha sido usado en Reservas Temporales',400);
    }

    //Verificaremos si el codigo ya ha sido insertado en Reservas
    $reserva_insertada = DB::select("SELECT IDReservacion FROM dbo.WebReservas WHERE IDReservacion = '$codigo'");
    if(null != $reserva_insertada)
    {
      return Response::json('El codigo de reservacion ya ha sido usado en Reservas',400);
    }

    // Validamos edad y si es menor a la menor establecido, no procede, en caso contrario si procede
    // Para efectos practicos si se sobre pasa de la edad la seteamos a la edad mas grande que tenemos definida, sin embargo guardamos la edad real

    //Buscamos los limites de edad
    $edad_menor = DB::select("SELECT Edad FROM dbo.WebEdad ORDER BY Edad ASC");
    $edad_mayor = DB::select("SELECT Edad FROM dbo.WebEdad ORDER BY Edad DESC");
    if(null == $edad_menor or null == $edad_mayor)
    {
      return Response::json('No se encontro la tabla de edades',500);
    }

    $edad_menor = $edad_menor[0]->Edad;
    $edad_mayor = $edad_mayor[0]->Edad;

    $edad_real = $edad;

    //Validamos la edad
    if($edad < $edad_menor)
    {
      return Response::json('Edad Invalida',400);
    }

    if($edad > $edad_mayor)
    {
      $edad = $edad_mayor;
    }

    //Encontramos los datos de las oficinas con el id_oficina y Id_oficina_retorno
    $oficina = DB::select("SELECT IDPlaza,IDTarifa,PorIVA FROM dbo.[WebOficina] WHERE IDOficina = '$id_oficina'");
    $oficina_regreso = DB::select("SELECT IDPlaza FROM dbo.[WebOficina] WHERE IDOficina = '$id_oficina_retorno'");

    if(null == $oficina || null == $oficina_regreso)
    {
      return Response::json('Oficina no encontrada', 500);
    }

    //Obtenemos datos de la oficina
    $oficina = $oficina[0];
    $id_plaza = $oficina->IDPlaza;
    $iva = 1 + ($oficina->PorIVA/100);

    //Obtenemos la plaza de la oficina de retorno
    $id_plaza_regreso = $oficina_regreso[0]->IDPlaza;


    //Buscamos la configuracion general de la empresa
    $configuracion = DB::select("SELECT DiasXMes, DiasXSem, HorasxDia,PorDescuentoPpgo,TitCDW,TitDP,TitPAI,TitPLI,TitPLIA
    ,TitMDW,TitERA,TitETS,TitCA,TitBS1,TitBS2,TitBS3,TitCM,TitGPS FROM dbo.WebConfiguracionGral");
    if($configuracion == null)
    {
      return Response::json('Configuracion no encontrada', 500);
    }
    $configuracion = $configuracion[0];


    //Buscamos el auto para hacer la cotizacion
    $grupo_auto = DB::select("SELECT b.Descripcion,a.IDSIPPCode1 FROM dbo.[WebGrupoAutos] a LEFT JOIN dbo.[WebSIPPCode] b ON  a.IDSIPPCode1 = b.IDSIPPCode
                                      WHERE a.IDPlaza = $id_plaza AND a.Activo = 1 AND a.IDSIPPCode1 = '$sipp_code'  ORDER BY b.Ordenamiento");
    if($grupo_auto == null)
    {
      return Response::json('Carro no encontrado', 500);
    }
    $grupo_auto = $grupo_auto[0];

    $tiempo  = Conversiones::getDaysHours($request['fecha_inicial'],$request['fecha_final']);
    if(gettype($tiempo) == 'integer')
    {
      return Response::json('Fechas Invalidas',400);
    }
    $dias_totales = $tiempo->dias;
    $horas_totales = $tiempo->horas;
    $fecha = $tiempo->fecha_inicial;
    $es_fin_semana = $tiempo->es_fin_semana;

    //Obtenemos las Tarifas e la fecha y con el IDTarifa obtenido
    $tarifas_filtradas =  Calculos::getTarifas($fecha,$id_promocion,$id_cliente,$id_agencia,$id_comisionista,$id_oficina,$id_plaza,$sub_tarifa);

    //Le colocamos su descripcion y SIPPCode
    $auto = new stdClass();
    $auto->descripcion = $grupo_auto->Descripcion;
    $auto->SIPPCode = $grupo_auto->IDSIPPCode1;

    //Validar Promocion
    $promocion = Validaciones::validarCodigoDescuento($id_promocion,$tiempo->fecha_inicial,$tiempo->fecha_final,$tiempo->dias,$id_plaza,$id_oficina,$fecha,$auto->SIPPCode,$id_agencia,$id_comisionista);;

    //Obtener cotizacion del auto
    $contador = 0;
    do
    {
      //Al principio sin error
      $error = 0;

      //Asiganamos la variable primero a null
      $tarifa = null;
      //Encontramos su tarifa
      $contador_tarifas = 0;
      foreach ($tarifas_filtradas as $tarifa_filtrada)
      {
        if($tarifa_filtrada->IDSIPPCode == $auto->SIPPCode)
        {
          $tarifa = $tarifa_filtrada;
          break;
        }
        $contador_tarifas += 1;
      }

      //Si se puede encontramos su tarifa y la calculamos
      if($tarifa != null)
      {
        //Le colocamos su Moneda y tarifa
        $auto_nuevo = new stdClass();
        $auto_nuevo->Moneda = $tarifa->Moneda;
        $auto_nuevo->tarifa = $tarifa->IDTarifa;
        //Se obtiene el plan code seguros a utilizar
        $precio_seguros = Calculos::getPlanCodeSeguros($id_cliente,$id_agencia,$id_comisionista,$id_oficina,$id_plaza,$auto->SIPPCode,$tarifa);

        $auto_nuevo->IDPCode = $tarifa->IDPCode;
        $auto_nuevo->IDPCS = $precio_seguros->IDPCS;

        //Calculamos el precio con los parametros
        $auto_nuevo->costo = Calculos::calcularPrecio($fecha,$tiempo,$es_fin_semana,$tarifa,$configuracion,$id_plaza,$id_plaza_regreso,$promocion,$iva,$id_oficina,$tarifa->IDSIPPCode,$edad,$seguros,$precio_seguros,false);

        //Codigo de error de calcular precio 1.- No se encontro precios de seguros del PCS 500
        //Quitamos la tarifa con error y alzamos la bandera de error
        if($auto_nuevo->costo->seguros->status == 1)
        {
          unset($tarifas_filtradas[$contador_tarifas]);
          $error = 1;
        }

        //Codigo de error de calcular precio 2.- No se encontro Plan code seguros 500
        //Quitamos la tarifa con error y alzamos la bandera de error
        if($auto_nuevo->costo->seguros->status == 2)
        {
          unset($tarifas_filtradas[$contador_tarifas]);
          $error = 1;
        }
      }
      else
      {
        //Si no, mandamos msg de error
        $auto_nuevo->costo = 'No definido';
      }

      //El contador es para cortar algun loop infinito
      $contador++;
    } while ($error == 1 && $contador < 20);

    $auto = $auto_nuevo;

    $costo = $auto->costo;
    $seguros = $costo->seguros;

    $tabla = 'WebReservasTMP';
    if($prepago == 1)
    {
      $costo->subtotal = $costo->subtotal_prepago;
      $costo->total = $costo->prepago;
      $costo->promocion_codigo = 0;
      $tabla = 'WebReservasTMP';
    }
    else
    {
      $costo->promocion_prepago = 0;
      $tabla = 'WebReservas';
    }

    $insercion = DB::insert("SET DATEFORMAT MDY;
    INSERT INTO dbo.$tabla
    ([IDReservacion]
      ,[TipoReservacion]
      ,[TipoMovRegistro]
      ,[IDPCode]
      ,[IDTarifa]
      ,[IDAgencia]
      ,[IDComisionista]
      ,[IDSIPPCode]
      ,[IDCodDescuento]
      ,[IDCteLeal]
      ,[IDPCS]
      ,[FechaHoraOperacion]
      ,[FechaControl]
      ,[OrigenReservacion]
      ,[Status]
      ,[ApellidoPaterno]
      ,[ApellidoMaterno]
      ,[Nombre]
      ,[NombreCompleto]
      ,[Moneda]
      ,[TDCaplicado]
      ,[IDOficinaReservacion]
      ,[FechaReservacion]
      ,[HoraReservacion]
      ,[IDOficinaRetorno]
      ,[FechaRetorno]
      ,[HoraRetorno]
      ,[IDPlazaReservacion]
      ,[IDPlazaRetorno]
      ,[DiasRenta]
      ,[HorasRenta]
      ,[DiasXMes]
      ,[DiasXSemana]
      ,[TarifaMesTTI]
      ,[TarifaMesTK]
      ,[CantidadMes]
      ,[ImporteMesTK]
      ,[TarifaSemanaTTI]
      ,[TarifaSemanaTK]
      ,[CantidadSemana]
      ,[ImporteSemanaTK]
      ,[TarifaDiaExtraTTI]
      ,[TarifaDiaExtraTK]
      ,[CantidadDiaExtra]
      ,[ImporteDiaExtraTK]
      ,[TarifaDiaTTI]
      ,[TarifaDiaTK]
      ,[CantidadDia]
      ,[ImporteDiaTK]
      ,[TarifaFinSemanaTTI]
      ,[TarifaFinSemanaTK]
      ,[CantidadDiaFinSemana]
      ,[ImporteDiaFinSemanaTK]
      ,[TarifaHoraTTI]
      ,[TarifaHoraTK]
      ,[CantidadHora]
      ,[ImporteHoraTK]
      ,[ImporteDescuentoTK]
      ,[TotalTK]
      ,[BanderaPrepago]
      ,[TitDP]
      ,[CostoDiaDP]
      ,[BanderaDP]
      ,[ImporteDP]
      ,[TitCDW]
      ,[CostoDiaCDW]
      ,[BanderaCDW]
      ,[ImporteCDW]
      ,[PorcentajeDeducibleRobo]
      ,[PorcentajeDeducibleDanos]
      ,[DeducibleCDW]
      ,[TitPAI]
      ,[CostoDiaPAI]
      ,[BanderaPAI]
      ,[ImportePAI]
      ,[TextoMontoMaximoPAI]
      ,[TitPLI]
      ,[CostoDiaPLI]
      ,[BanderaPLI]
      ,[ImportePLI]
      ,[MontoMaximoPLI]
      ,[TitPLIA]
      ,[CostoDiaPLIA]
      ,[BanderaPLIA]
      ,[ImportePLIA]
      ,[MontoMaximoPLIA]
      ,[TitMDW]
      ,[CostoDiaMDW]
      ,[BanderaMDW]
      ,[ImporteMDW]
      ,[MontoMaximoMDW]
      ,[TotalCoberturas]
      ,[TitERA]
      ,[CostoDiaERA]
      ,[BanderaERA]
      ,[CantidadERA]
      ,[ImporteERA]
      ,[TitETS]
      ,[CostoDiaETS]
      ,[BanderaETS]
      ,[CantidadETS]
      ,[ImporteETS]
      ,[TitCA]
      ,[CostoDiaCA]
      ,[BanderaCA]
      ,[CantidadCA]
      ,[ImporteCA]
      ,[TitBS1]
      ,[CostoDiaBS1]
      ,[BanderaBS1]
      ,[CantidadBS1]
      ,[ImporteBS1]
      ,[TitBS2]
      ,[CostoDiaBS2]
      ,[BanderaBS2]
      ,[CantidadBS2]
      ,[ImporteBS2]
      ,[TitBS3]
      ,[CostoDiaBS3]
      ,[BanderaBS3]
      ,[CantidadBS3]
      ,[ImporteBS3]
      ,[TitCM]
      ,[CostoDiaCM]
      ,[BanderaCM]
      ,[CantidadCM]
      ,[ImporteCM]
      ,[TitGPS]
      ,[CostoDiaGPS]
      ,[BanderaGPS]
      ,[CantidadGPS]
      ,[ImporteGPS]
      ,[CargoPickup]
      ,[ComentarioPickup]
      ,[CargoDropOff]
      ,[TotalExtras]
      ,[BanderaLCRFee]
      ,[CargoLCRFee]
      ,[BanderaSCFee]
      ,[CargoSCFee]
      ,[AplicacionCCFee]
      ,[BanderaCCFee]
      ,[CargoCCFee]
      ,[BanderaAirportFee]
      ,[PorAirportFee]
      ,[MontoAirportFee]
      ,[BanderaHotelFee]
      ,[PorHotelFee]
      ,[MontoHotelFee]
      ,[ImporteDescuento]
      ,[ImporteDescuentoPpgo]
      ,[Subtotal]
      ,[BanderaIVA]
      ,[PorIva]
      ,[MontoIva]
      ,[Total]
      ,[TotalOrigen]
      ,[Comentarios]
      ,[MensajeEstimado]
      ,[Email]
      ,[MontoPagado]
      ,[SerieCFD]
      ,[FolioCFD]
      ,[FechaCFD]
      ,[MetodoPago]
      ,[FormaPago]
      ,[CuentaPago]
      ,[Banco]
      ,[TipoTdC]
      ,[NumeroTdC]
      ,[CodSegTdC]
      ,[TC_Expira]
      ,[TC_Aut]
      ,[Dom_Local]
      ,[Dom_Permanente]
      ,[Edad]
      ,[Telefono]
      ,[Con_Lic]
      ,[Con_Lic_Expira]
      ,[Con_Lic_Edo]
      ,[CteVIP]
      ,[Aerolinea]
      ,[Vuelo]
      ,[StatusEnvioMail]
      ,[FechaLecturaJR]
      ,[FechaEnvioMail])
      VALUES ('$codigo'
        ,'WEB'
        ,'A'
        ,'$auto->IDPCode'
        ,'$auto->tarifa'
        ,'$id_agencia'
        ,'$id_comisionista'
        ,'$sipp_code'
        ,'$id_promocion'
        ,'$id_cliente'
        ,'$auto->IDPCS'
        ,getdate()
        ,getdate()
        ,'1.1.1.1'
        ,'Activa'
        ,'$paterno'
        ,'$materno'
        ,'$nombre'
        ,'$nombre_completo'
        ,'$tarifa->Moneda'
        ,1
        ,'$id_oficina'
        ,'$tiempo->fecha_inicial_real'
        ,NULL
        ,'$id_oficina_retorno'
        ,'$tiempo->fecha_final_real'
        ,NULL
        ,'$id_plaza'
        ,'$id_plaza_regreso'
       ,$dias_totales
       ,$horas_totales
       ,$costo->DiasXMes
       ,$costo->DiasXSem
       ,0
       ,$costo->precio_por_mes
       ,$costo->meses_rentados
       ,$costo->costo_mes
       ,0
       ,$costo->precio_por_semana
       ,$costo->semanas_rentadas
       ,$costo->costo_semana
       ,0
       ,$costo->precio_por_dia_extra
       ,$costo->dias_extras_rentados
       ,$costo->costo_dia_extra
       ,0
       ,$costo->precio_por_dia
       ,$costo->dias_rentados
       ,$costo->costo_dia
       ,0
       ,$costo->precio_por_dia_fin_semana
       ,$costo->dias_fin_semana
       ,$costo->costo_dia_fin_semana
       ,0
       ,$costo->precio_por_hora
       ,$costo->horas_rentadas
       ,$costo->costo_hora
       ,0
       ,$costo->total_TK
       ,$prepago
        ,'$configuracion->TitDP'
       ,$seguros->DP / $seguros->dias
       ,$dp
       ,$seguros->DP
        ,'$configuracion->TitCDW'
       ,$seguros->CDW / $seguros->dias
       ,$cdw
       ,$seguros->CDW
       ,NULL
       ,NULL
       ,NULL
        ,'$configuracion->TitPAI'
       ,$seguros->PAI / $seguros->dias
       ,$pai
       ,$seguros->PAI
        ,''
        ,'$configuracion->TitPLI'
       ,$seguros->PLI / $seguros->dias
       ,$pli
       ,$seguros->PLI
       ,NULL
        ,'$configuracion->TitPLIA'
       ,$seguros->PLIA / $seguros->dias
       ,$plia
       ,$seguros->PLIA
       ,NULL
        ,'$configuracion->TitMDW'
       ,$seguros->MDW / $seguros->dias
       ,$mdw
       ,$seguros->MDW
       ,NULL
       ,$costo->total_seguros
        ,'$configuracion->TitERA'
       ,$seguros->ERA / $seguros->dias
       ,$era
       ,1
       ,$seguros->ERA
        ,'$configuracion->TitETS'
       ,$seguros->ETS / $seguros->dias
       ,$ets
       ,1
       ,$seguros->ETS
        ,'$configuracion->TitCA'
       ,$seguros->CA / $seguros->dias
       ,$ca
       ,1
       ,$seguros->CA
        ,'$configuracion->TitBS1'
       ,$seguros->BS1 / $seguros->dias
       ,$bs1
       ,1
       ,$seguros->BS1
        ,'$configuracion->TitBS2'
       ,$seguros->BS2 / $seguros->dias
       ,$bs2
       ,1
       ,$seguros->BS2
        ,'$configuracion->TitBS3'
       ,$seguros->BS3 / $seguros->dias
       ,$bs3
       ,1
       ,$seguros->BS3
        ,'$configuracion->TitCM'
       ,$seguros->CM / $seguros->dias
       ,$cm
       ,1
       ,$seguros->CM
      ,'$configuracion->TitGPS'
       ,$seguros->GPS / $seguros->dias
       ,$gps
       ,1
       ,$seguros->GPS
       ,0
        ,'$key'
       ,$costo->drop_off
       ,$costo->total_extras
       ,$seguros->LCRF_bandera
       ,$seguros->LCRF
       ,$seguros->SCF_bandera
       ,$seguros->SCF
       ,NULL
       ,NULL
       ,NULL
       ,$seguros->AF_bandera
       ,$seguros->AF
       ,$seguros->monto_AF
       ,$seguros->HF_bandera
       ,$seguros->HF
       ,$seguros->monto_HF
       ,$costo->promocion_codigo
       ,$costo->promocion_prepago
       ,$costo->subtotal
       ,1
       ,$iva
       ,$costo->total - $costo->subtotal
       ,$costo->total
       ,0
       ,' '
       ,' '
       ,'$email'
       ,0
       ,' '
       ,' '
       ,NULL
       ,' '
       ,' '
       ,' '
       ,' '
       ,' '
       ,' '
       ,' '
       ,NULL
       ,' '
       ,' '
       ,' '
       ,$edad_real
       ,'$telefono'
       ,'$licencia'
       ,'$licencia_expira'
       ,'$licencia_estado'
      ,0
       ,'$aerolinea'
       ,'$vuelo'
      ,0
       ,NULL
       ,NULL)");

    //Retornamos los autos con sus tarifas
    return Response::json($insercion,201);
  }

  //Optimizado
  public function getDomicilioOficina(Request $request)
  {
    //Obtenemos el request y checamos si tiene algun Caracter
    //de algun slq injection
    $request_viejo = $request->all();
    $request = Conversiones::checkArray($request);
    $diferencia = array_diff($request, $request_viejo);
    if([] != $diferencia)
    {
      return Response::json("Caracteres especiales no soportados",400);
    }

    //Revisar si hay token
    if(!isset($request['token']))
    {
      return Response::json('Token no enviado',400);
    }

    $token = $request['token'];

    //Buscar token
    $count = DB::select("SELECT COUNT(Token) as numero FROM dbo.[WebToken] WHERE Token = '$token' AND GETDATE() < FechaCaducidad");

    $count = $count[0]->numero;

    //Validar token
    if($count < 1)
    {
      return Response::json('Token no valido',400);
    }

    //Verificamos que los parametros se mandaron correctamente
    if(!isset($request['id']))
    {
      return Response::json('Parametros no mandados correctamente',400);
    }
    //Obtenemos el id
    $id = $request['id'];

    //Buscamos la oficina
    $data = DB::select("SELECT [Calle]
                      ,[NumExt]
                      ,[NumInt]
                      ,[Colonia]
                      ,[Ciudad]
                      ,[Localidad]
                      ,[Estado]
                      ,[Pais]
                      ,[CodigoPostal]
                      ,[Telefono1]
                      ,[Telefono2]
                      ,[Nombre] as nombre
                  FROM dbo.[WebOficina] WHERE IDOficina = '$id'");

    //Regresamos los datos encontrados
    if(null != $data)
    {
      $data = $data[0];
      return Response::json($data,201);
    }
    return Response::json('No se ha encontrado la oficina',400);
  }

  //Optimizado
  public function getReserva(Request $request)
  {
    //Obtenemos el request y checamos si tiene algun Caracter
    //de algun slq injection
    $request_viejo = $request->all();
    $request = Conversiones::checkArray($request);
    $diferencia = array_diff($request, $request_viejo);
    if([] != $diferencia)
    {
      return Response::json("Caracteres especiales no soportados",400);
    }

    //Revisar si hay token
    if(!isset($request['token']))
    {
      return Response::json('Token no enviado',400);
    }

    $token = $request['token'];

    //Buscar token
    $count = DB::select("SELECT COUNT(Token) as numero FROM dbo.[WebToken] WHERE Token = '$token' AND GETDATE() < FechaCaducidad");

    $count = $count[0]->numero;

    //Validar token
    if($count < 1)
    {
      return Response::json('Token no valido',400);
    }

    //Verificamos que los parametros se mandaron correctamente
    if(!(isset($request['codigo']) && isset($request['apellido']) && isset($request['temporal'])))
    {
      return Response::json('Parametros no mandados correctamente',400);
    }

    //Cachamos variables forzosas
    $codigo = $request['codigo'];
    $apellido = $request['apellido'];
    $temporal = $request['temporal'];

    //Si es temporal buscamos el la tabla de temporales si no en la otra
    //Si no lo encontramos regresamos error
    if(1 == $temporal)
    {
      $reserva = DB::select("SELECT  WebSIPPCode.Descripcion,WebReservasTMP.*
                          FROM WebReservasTMP,WebSIPPCode WHERE WebReservasTMP.IDReservacion = '$codigo' AND WebReservasTMP.ApellidoPaterno = '$apellido' AND WebSIPPCode.IDSIPPCode = WebReservasTMP.IDSIPPCode");
      if(null == $reserva)
      {
        return Response::json('reserva no encontrada',500);
      }
    }
    else
    {
      $reserva = DB::select("SELECT  WebSIPPCode.Descripcion,WebReservas.*
                            FROM WebReservas,WebSIPPCode WHERE WebReservas.IDReservacion = '$codigo' AND WebReservas.ApellidoPaterno = '$apellido' AND WebSIPPCode.IDSIPPCode = WebReservas.IDSIPPCode");
      if(null == $reserva)
      {
        return Response::json('reserva no encontrada',500);
      }
    }

    //Formamos el objeto de reserva

    $reserva = $reserva[0];
    $fees = new stdClass();
    $fees->BanderaLCRFee = $reserva->BanderaLCRFee;
    $fees->CargoLCRFee = $reserva->CargoLCRFee;

    $fees->BanderaLCRFee = $reserva->BanderaLCRFee;
    $fees->CargoSCFee = $reserva->CargoSCFee;

    $fees->BanderaAirportFee = $reserva->BanderaAirportFee;
    $fees->PorAirportFee = $reserva->PorAirportFee;
    $fees->MontoAirportFee = $reserva->MontoAirportFee;

    $fees->BanderaHotelFee = $reserva->BanderaHotelFee;
    $fees->PorHotelFee = $reserva->PorHotelFee;
    $fees->MontoHotelFee = $reserva->MontoHotelFee;

    $dp = new stdClass();
    $dp->Tit = $reserva->TitDP;
    $dp->CostoDia = $reserva->CostoDiaDP;
    $dp->Bandera = $reserva->BanderaDP;
    $dp->Importe = $reserva->ImporteDP;

    $cdw = new stdClass();
    $cdw->Tit = $reserva->TitCDW;
    $cdw->CostoDia = $reserva->CostoDiaCDW;
    $cdw->Bandera = $reserva->BanderaCDW;
    $cdw->Importe = $reserva->ImporteCDW;

    $pai = new stdClass();
    $pai->Tit = $reserva->TitPAI;
    $pai->CostoDia = $reserva->CostoDiaPAI;
    $pai->Bandera = $reserva->BanderaPAI;
    $pai->Importe = $reserva->ImportePAI;

    $pli = new stdClass();
    $pli->Tit = $reserva->TitPLI;
    $pli->CostoDia = $reserva->CostoDiaPLI;
    $pli->Bandera = $reserva->BanderaPLI;
    $pli->Importe = $reserva->ImportePLI;

    $plia = new stdClass();
    $plia->Tit = $reserva->TitPLIA;
    $plia->CostoDia = $reserva->CostoDiaPLIA;
    $plia->Bandera = $reserva->BanderaPLIA;
    $plia->Importe = $reserva->ImportePLIA;

    $mdw = new stdClass();
    $mdw->Tit = $reserva->TitMDW;
    $mdw->CostoDia = $reserva->CostoDiaMDW;
    $mdw->Bandera = $reserva->BanderaMDW;
    $mdw->Importe = $reserva->ImporteMDW;

    $era = new stdClass();
    $era->Tit = $reserva->TitERA;
    $era->CostoDia = $reserva->CostoDiaERA;
    $era->Bandera = $reserva->BanderaERA;
    $era->Importe = $reserva->ImporteERA;

    $ets = new stdClass();
    $ets->Tit = $reserva->TitETS;
    $ets->CostoDia = $reserva->CostoDiaETS;
    $ets->Bandera = $reserva->BanderaETS;
    $ets->Importe = $reserva->ImporteETS;

    $ca = new stdClass();
    $ca->Tit = $reserva->TitCA;
    $ca->CostoDia = $reserva->CostoDiaCA;
    $ca->Bandera = $reserva->BanderaCA;
    $ca->Importe = $reserva->ImporteCA;

    $bs1 = new stdClass();
    $bs1->Tit = $reserva->TitBS1;
    $bs1->CostoDia = $reserva->CostoDiaBS1;
    $bs1->Bandera = $reserva->BanderaBS1;
    $bs1->Importe = $reserva->ImporteBS1;

    $bs2 = new stdClass();
    $bs2->Tit = $reserva->TitBS2;
    $bs2->CostoDia = $reserva->CostoDiaBS2;
    $bs2->Bandera = $reserva->BanderaBS2;
    $bs2->Importe = $reserva->ImporteBS2;

    $bs3 = new stdClass();
    $bs3->Tit = $reserva->TitBS3;
    $bs3->CostoDia = $reserva->CostoDiaBS3;
    $bs3->Bandera = $reserva->BanderaBS3;
    $bs3->Importe = $reserva->ImporteBS3;

    $cm = new stdClass();
    $cm->Tit = $reserva->TitCM;
    $cm->CostoDia = $reserva->CostoDiaCM;
    $cm->Bandera = $reserva->BanderaCM;
    $cm->Importe = $reserva->ImporteCM;

    $gps = new stdClass();
    $gps->Tit = $reserva->TitGPS;
    $gps->CostoDia = $reserva->CostoDiaGPS;
    $gps->Bandera = $reserva->BanderaGPS;
    $gps->Importe = $reserva->ImporteGPS;

    $seguros = [$dp,$cdw,$pai,$pli,$plia,$mdw,$era,$ets,$ca,$bs1,$bs2,$bs3,$cm,$gps];


    $reserva_json = new stdClass();

    $reserva_json->TarifaMesTK = $reserva->TarifaMesTK;
    $reserva_json->CantidadMes = $reserva->CantidadMes;
    $reserva_json->ImporteMesTK = $reserva->ImporteMesTK;


    $reserva_json->TarifaSemanaTK = $reserva->TarifaSemanaTK;
    $reserva_json->CantidadSemana = $reserva->CantidadSemana;
    $reserva_json->ImporteSemanaTK = $reserva->ImporteSemanaTK;


    $reserva_json->TarifaDiaExtraTK = $reserva->TarifaDiaExtraTK;
    $reserva_json->CantidadDiaExtra = $reserva->CantidadDiaExtra;
    $reserva_json->ImporteDiaExtraTK = $reserva->ImporteDiaExtraTK;


    $reserva_json->TarifaDiaTK = $reserva->TarifaDiaTK;
    $reserva_json->CantidadDia = $reserva->CantidadDia;
    $reserva_json->ImporteDiaTK = $reserva->ImporteDiaTK;


    $reserva_json->TarifaFinSemanaTK = $reserva->TarifaFinSemanaTK;
    $reserva_json->CantidadDiaFinSemana = $reserva->CantidadDiaFinSemana;
    $reserva_json->ImporteDiaFinSemanaTK = $reserva->ImporteDiaFinSemanaTK;


    $reserva_json->TarifaHoraTK = $reserva->TarifaHoraTK;
    $reserva_json->CantidadHora = $reserva->CantidadHora;
    $reserva_json->ImporteHoraTK = $reserva->ImporteHoraTK;

    //Agencia
    $reserva_json->IDAgencia = $reserva->IDAgencia;
    $nombre = DB::select("SELECT RazonSocial FROM WebAgencia WHERE IDAgencia = '$reserva_json->IDAgencia'");
    if($nombre != null)
    {
      $nombre = $nombre[0]->RazonSocial;
      $reserva_json->nombre_agencia = $nombre;
    }
    else
    {
      $reserva_json->nombre_agencia = 'Sin agencia';
    }
    //Comisionista
    $reserva_json->IDComisionista = $reserva->IDComisionista;
    $nombre = DB::select("SELECT NombreComisionista FROM WebComisionista WHERE IDComisionista = '$reserva_json->IDComisionista'");
    if($nombre != null)
    {
      $nombre = $nombre[0]->NombreComisionista;
      $reserva_json->nombre_comisionista = $nombre;
    }
    else
    {
      $reserva_json->nombre_comisionista = 'Sin comisionista';
    }

    if($reserva_json->CantidadHora > 0)
    {
      $dias_seguros = $reserva->DiasRenta + 1;
    }
    else
    {
      $dias_seguros = $reserva->DiasRenta;
    }

    switch ($reserva->TipoMovRegistro) {
      case 'A':
        $reserva_json->TipoMovRegistro = 'Active';
        break;
      case 'C':
        $reserva_json->TipoMovRegistro = 'Cancelada';
        break;
      default:
        $reserva_json->TipoMovRegistro = 'No Activa';
        break;
    }
    $plaza = DB::select("SELECT Telefono1, Telefono2, Email FROM WebPlaza WHERE IDPlaza =  $reserva->IDPlazaReservacion");
    if(null == $plaza)
    {
      return Response::json('reserva no encontrada',500);
    }
    $plaza = $plaza[0];

    $reserva_json->IDTarifa = $reserva->IDTarifa;
    $reserva_json->autorizacion_Paypal = $reserva->TC_Aut;
    $reserva_json->IDComisionista = $reserva->IDComisionista;
    $reserva_json->IDCteLeal = $reserva->IDCteLeal;
    $reserva_json->Telefono1_plaza = $plaza->Telefono1;
    $reserva_json->Telefono2_plaza = $plaza->Telefono2;
    $reserva_json->Email_plaza = $plaza->Email;

    $reserva_json->Descripcion = $reserva->Descripcion;
    $reserva_json->IDReservacion = $reserva->IDReservacion;
    $reserva_json->IDSIPPCode = $reserva->IDSIPPCode;
    $reserva_json->FechaHoraOperacion = $reserva->FechaHoraOperacion;
    $reserva_json->FechaControl = $reserva->FechaControl;
    $reserva_json->ApellidoPaterno = $reserva->ApellidoPaterno;
    $reserva_json->ApellidoMaterno = $reserva->ApellidoMaterno;
    $reserva_json->Nombre = $reserva->Nombre;
    $reserva_json->NombreCompleto = $reserva->NombreCompleto;
    $reserva_json->Moneda = $reserva->Moneda;
    $reserva_json->IDOficinaReservacion = $reserva->IDOficinaReservacion;
    $reserva_json->FechaReservacion = $reserva->FechaReservacion;
    $reserva_json->IDOficinaRetorno = $reserva->IDOficinaRetorno;
    $reserva_json->FechaRetorno = $reserva->FechaRetorno;
    $reserva_json->DiasRenta = $reserva->DiasRenta;
    $reserva_json->DiasSeguros = $dias_seguros;
    $reserva_json->TotalTK = $reserva->TotalTK;
    $reserva_json->TotalCoberturas = $reserva->TotalCoberturas;
    $reserva_json->TotalExtras = $reserva->TotalExtras;
    $reserva_json->Subtotal = $reserva->Subtotal;
    $reserva_json->BanderaIVA = $reserva->BanderaIVA;
    $reserva_json->PorIva = $reserva->PorIva;
    $reserva_json->MontoIva = $reserva->MontoIva;
    $reserva_json->Total = $reserva->Total;
    $reserva_json->TotalOrigen = $reserva->TotalOrigen;
    $reserva_json->Email = $reserva->Email;
    $reserva_json->Edad = $reserva->Edad;
    $reserva_json->Telefono = $reserva->Telefono;
    $reserva_json->IDAgencia = $reserva->IDAgencia;
    $reserva_json->IDComisionista = $reserva->IDComisionista;
    $reserva_json->IDCodDescuento = $reserva->IDCodDescuento;
    $reserva_json->IDCteLeal = $reserva->IDCteLeal;
    $reserva_json->CargoPickup = $reserva->CargoPickup;
    $reserva_json->ComentarioPickup = $reserva->ComentarioPickup;
    $reserva_json->CargoDropOff = $reserva->CargoDropOff;
    $reserva_json->ImporteDescuento = $reserva->ImporteDescuento;
    $reserva_json->ImporteDescuentoPpgo = $reserva->ImporteDescuentoPpgo;
    $reserva_json->descuento_al_subtotal = $reserva->ImporteDescuento + $reserva->ImporteDescuentoPpgo;
    $reserva_json->HorasRenta = $reserva->HorasRenta;
    $reserva_json->BanderaPrepago = $reserva->BanderaPrepago;
    $reserva_json->Moneda = $reserva->Moneda;
    $reserva_json->MontoPagado = $reserva->MontoPagado;
    $reserva_json->Vuelo = $reserva->Vuelo;
    $reserva_json->Aerolinea = $reserva->Aerolinea;

    $reserva_json->seguros = $seguros;

    $reserva_json->fees = $fees;

    //Oficina salida
    $oficina = DB::select("SELECT Latitud,Longitud FROM WebOficina WHERE IDOficina =  '$reserva->IDOficinaReservacion'");
    if(null == $oficina)
    {
      return Response::json('reserva no encontrada',500);
    }
    $oficina = $oficina[0];

    $reserva_json->LatitudSalida = $oficina->Latitud;
    $reserva_json->LongitudSalida = $oficina->Longitud;

    //Oficina regreso
    $oficina = DB::select("SELECT Latitud,Longitud FROM WebOficina WHERE IDOficina =  '$reserva->IDOficinaRetorno'");
    if(null == $oficina)
    {
      return Response::json('reserva no encontrada',500);
    }
    $oficina = $oficina[0];

    $reserva_json->LatitudRegreso = $oficina->Latitud;
    $reserva_json->LongitudRegreso = $oficina->Longitud;

    return Response::json($reserva_json,201);
  }
  public function activarPrepago(Request $request)
  {
    //Revisar la entrada de datos
    $request_viejo = $request->all();
    $request = Conversiones::checkArray($request);
    $diferencia = array_diff($request, $request_viejo);
    if([] != $diferencia)
    {
      return Response::json("Caracteres especiales no soportados",400);
    }

    //Revisar si hay token
    if(!isset($request['token']))
    {
      return Response::json('Token no enviado',400);
    }

    $token = $request['token'];

    //Buscar token
    $count = DB::select("SELECT COUNT(Token) as numero FROM dbo.[WebToken] WHERE Token = '$token' AND GETDATE() < FechaCaducidad");

    $count = $count[0]->numero;

    //Validar token
    if($count < 1)
    {
      return Response::json('Token no valido',400);
    }

    //Buscar parametros necesarios
    if(!(isset($request['codigo']) && isset($request['apellido']) && isset($request['autorizacion'])))
    {
      return Response::json('Parametros necesarios no mandados',400);
    }

    $autorizacion = $request['autorizacion'];
    $codigo = $request['codigo'];
    $apellido = $request['apellido'];

    //Buscar Reserva
    $reserva = DB::select("SELECT  WebSIPPCode.Descripcion,WebReservasTMP.* FROM WebReservasTMP,WebSIPPCode
    WHERE WebReservasTMP.IDReservacion = '$codigo' AND WebReservasTMP.ApellidoPaterno = '$apellido' AND WebSIPPCode.IDSIPPCode = WebReservasTMP.IDSIPPCode");

    //Validar que encontramos reserva
    if(null == $reserva)
    {
      return Response::json('reserva no encontrada',500);
    }

    //Buscamos si la autorizacion ya existe
    $numero_aut = DB::select("SELECT * FROM WebReservas WHERE TC_Aut = '$autorizacion'");
    if($numero_aut != null)
    {
      return Response::json('Esa autorizacion ya ha sido utilizada',500);
    }

    //Cambiar solo el client_id y el secret
    $client_id_paypal = env('CLIENT_ID_PAYPAL');
    $secret_paypal = env('SECRET_PAYPAL');
    $url = "https://api.paypal.com/v1/oauth2/token";

    $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_USERPWD => $client_id_paypal . ":" . $secret_paypal,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_SSL_VERIFYPEER => 0, //que no verifique ssl
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "grant_type=client_credentials",
        CURLOPT_HTTPHEADER => array(
          "Accept: application/json",
          "Accept-Language: en_US",
          "Content-Type: application/x-www-form-urlencoded"
        ),
      ));

      $response = curl_exec($curl);

      curl_close($curl);
      $response = json_decode($response);

      //Verificar que regreso token
      if(!isset($response->access_token))
      {
        return Response::json("No se obtuvo el token para verificar",400);
      }

    //Pedir token para el verificar pagos
    $curl = curl_init();

    //Nota: Cambiar  el secret en la authorizacion, para esto debemos irnos a Postman
    //Poner la URL en https://api.sandbox.paypal.com/v1/oauth2/token?grant_type=client_credentials
    //Authorization en basic auth, username = <<Client ID>> y de password = <<secret>>
    //Sacamos los parametros del header y se los ponemos
    //Si no es version de prueba eliminar la palabra sandbox
    //En el cliente (pagina web) cambiar el client-id
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://api.paypal.com/v1/oauth2/token",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_SSL_VERIFYPEER => 0, //que no verifique ssl
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => "grant_type=client_credentials",
      CURLOPT_HTTPHEADER => array(
        "Accept: application/json",
        "Accept-Language: en_US",
        "Authorization: Bearer $response->access_token",
        "Content-Type: application/x-www-form-urlencoded"
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    $response = json_decode($response);

    //Verificar que regreso token
    if(!isset($response->access_token))
    {
      return Response::json("No se obtuvo el token para verificar",500);
    }


    //Verificar el pago en paypal
    $curl = curl_init();

    //Si no es version de prueba eliminar la palabra sandbox
    curl_setopt_array($curl, array(
    CURLOPT_URL => "https://api.paypal.com/v2/checkout/orders/".$autorizacion,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_SSL_VERIFYPEER => 0,  //que no verifique ssl
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
      "Content-Type: application/json",
      "Authorization: Bearer $response->access_token"
    ),
    ));

    //Checamos la autorizacion en paypal
    $response = curl_exec($curl);
    curl_close($curl);
    $response = json_decode($response);

    if(isset($response->error))
    {
      return Response::json("No se encontro el pago en paypal con el error $response->error",500);
    }

    //Establecemos la moneda a pagar
    $moneda = 'MXN';
    if('USD' == $reserva[0]->Moneda)
    {
      $moneda = 'USD';
    }
    if('MN' == $reserva[0]->Moneda)
    {
      $moneda = 'MXN';
    }
    //Verificamos la que la moneda sea igual
    if($response->purchase_units[0]->amount->currency_code != $moneda)
    {
      return Response::json('Monedas a pagar diferentes',500);
    }

    //Checamos el monto a pagar
    $monto_pagado = $response->purchase_units[0]->amount->value;
    if ($reserva[0]->Total > $monto_pagado)
    {
      return Response::json('No ha pagado lo suficiente',500);
    }

    if(isset($response->status))
    {
        if('COMPLETED' != $response->status)
        {
          return Response::json('No se ha completado el pagado',500);
        }
    }
    else
    {
      return Response::json('No se ha pagado',500);
    }

    //Poner la autorizacion
    $update = DB::update("UPDATE WebReservasTMP SET TC_Aut = '$autorizacion',MontoPagado = $monto_pagado WHERE IDReservacion = '$codigo' AND ApellidoPaterno = '$apellido';");

    $move = DB::insert("SET DATEFORMAT MDY;
    INSERT INTO WebReservas SELECT [IDReservacion]
          ,[TipoReservacion]
          ,[TipoMovRegistro]
          ,[IDPCode]
          ,[IDTarifa]
          ,[IDAgencia]
          ,[IDComisionista]
          ,[IDSIPPCode]
          ,[IDCodDescuento]
          ,[IDCteLeal]
          ,[IDPCS]
          ,[FechaHoraOperacion]
          ,[FechaControl]
          ,[OrigenReservacion]
          ,[Status]
          ,[ApellidoPaterno]
          ,[ApellidoMaterno]
          ,[Nombre]
          ,[NombreCompleto]
          ,[Moneda]
          ,[TDCaplicado]
          ,[IDOficinaReservacion]
          ,[FechaReservacion]
          ,[HoraReservacion]
          ,[IDOficinaRetorno]
          ,[FechaRetorno]
          ,[HoraRetorno]
          ,[IDPlazaReservacion]
          ,[IDPlazaRetorno]
          ,[DiasRenta]
          ,[HorasRenta]
          ,[DiasXMes]
          ,[DiasXSemana]
          ,[TarifaMesTTI]
          ,[TarifaMesTK]
          ,[CantidadMes]
          ,[ImporteMesTK]
          ,[TarifaSemanaTTI]
          ,[TarifaSemanaTK]
          ,[CantidadSemana]
          ,[ImporteSemanaTK]
          ,[TarifaDiaExtraTTI]
          ,[TarifaDiaExtraTK]
          ,[CantidadDiaExtra]
          ,[ImporteDiaExtraTK]
          ,[TarifaDiaTTI]
          ,[TarifaDiaTK]
          ,[CantidadDia]
          ,[ImporteDiaTK]
          ,[TarifaFinSemanaTTI]
          ,[TarifaFinSemanaTK]
          ,[CantidadDiaFinSemana]
          ,[ImporteDiaFinSemanaTK]
          ,[TarifaHoraTTI]
          ,[TarifaHoraTK]
          ,[CantidadHora]
          ,[ImporteHoraTK]
          ,[ImporteDescuentoTK]
          ,[TotalTK]
          ,[BanderaPrepago]
          ,[TitDP]
          ,[CostoDiaDP]
          ,[BanderaDP]
          ,[ImporteDP]
          ,[TitCDW]
          ,[CostoDiaCDW]
          ,[BanderaCDW]
          ,[ImporteCDW]
          ,[PorcentajeDeducibleRobo]
          ,[PorcentajeDeducibleDanos]
          ,[DeducibleCDW]
          ,[TitPAI]
          ,[CostoDiaPAI]
          ,[BanderaPAI]
          ,[ImportePAI]
          ,[TextoMontoMaximoPAI]
          ,[TitPLI]
          ,[CostoDiaPLI]
          ,[BanderaPLI]
          ,[ImportePLI]
          ,[MontoMaximoPLI]
          ,[TitPLIA]
          ,[CostoDiaPLIA]
          ,[BanderaPLIA]
          ,[ImportePLIA]
          ,[MontoMaximoPLIA]
          ,[TitMDW]
          ,[CostoDiaMDW]
          ,[BanderaMDW]
          ,[ImporteMDW]
          ,[MontoMaximoMDW]
          ,[TotalCoberturas]
          ,[TitERA]
          ,[CostoDiaERA]
          ,[BanderaERA]
          ,[CantidadERA]
          ,[ImporteERA]
          ,[TitETS]
          ,[CostoDiaETS]
          ,[BanderaETS]
          ,[CantidadETS]
          ,[ImporteETS]
          ,[TitCA]
          ,[CostoDiaCA]
          ,[BanderaCA]
          ,[CantidadCA]
          ,[ImporteCA]
          ,[TitBS1]
          ,[CostoDiaBS1]
          ,[BanderaBS1]
          ,[CantidadBS1]
          ,[ImporteBS1]
          ,[TitBS2]
          ,[CostoDiaBS2]
          ,[BanderaBS2]
          ,[CantidadBS2]
          ,[ImporteBS2]
          ,[TitBS3]
          ,[CostoDiaBS3]
          ,[BanderaBS3]
          ,[CantidadBS3]
          ,[ImporteBS3]
          ,[TitCM]
          ,[CostoDiaCM]
          ,[BanderaCM]
          ,[CantidadCM]
          ,[ImporteCM]
          ,[TitGPS]
          ,[CostoDiaGPS]
          ,[BanderaGPS]
          ,[CantidadGPS]
          ,[ImporteGPS]
          ,[CargoPickup]
          ,[ComentarioPickup]
          ,[CargoDropOff]
          ,[TotalExtras]
          ,[BanderaLCRFee]
          ,[CargoLCRFee]
          ,[BanderaSCFee]
          ,[CargoSCFee]
          ,[AplicacionCCFee]
          ,[BanderaCCFee]
          ,[CargoCCFee]
          ,[BanderaAirportFee]
          ,[PorAirportFee]
          ,[MontoAirportFee]
          ,[BanderaHotelFee]
          ,[PorHotelFee]
          ,[MontoHotelFee]
          ,[ImporteDescuento]
          ,[ImporteDescuentoPpgo]
          ,[Subtotal]
          ,[BanderaIVA]
          ,[PorIva]
          ,[MontoIva]
          ,[Total]
          ,[TotalOrigen]
          ,[Comentarios]
          ,[MensajeEstimado]
          ,[Email]
          ,[MontoPagado]
          ,[SerieCFD]
          ,[FolioCFD]
          ,[FechaCFD]
          ,[MetodoPago]
          ,[FormaPago]
          ,[CuentaPago]
          ,[Banco]
          ,[TipoTdC]
          ,[NumeroTdC]
          ,[CodSegTdC]
          ,[TC_Expira]
          ,[TC_Aut]
          ,[Dom_Local]
          ,[Dom_Permanente]
          ,[Edad]
          ,[Telefono]
          ,[Con_Lic]
          ,[Con_Lic_Expira]
          ,[Con_Lic_Edo]
          ,[CteVIP]
          ,[Aerolinea]
          ,[Vuelo]
          ,[StatusEnvioMail]
          ,[FechaLecturaJR]
          ,[FechaEnvioMail] FROM WebReservasTMP WHERE IDReservacion = '$codigo' AND ApellidoPaterno = '$apellido';");

    if(true == $move)
    {
      $eliminar = DB::delete("DELETE FROM WebReservasTMP WHERE IDReservacion = '$codigo' AND ApellidoPaterno = '$apellido';");

      return Response::json(true,200);
    }
    else
    {
      return Response::json('No se pudo mover el registro, o no se encontro',500);
    }

    return Response::json(true,200);
  }

  public function activarPrepagoStripe(Request $request)
  {
    //Revisar la entrada de datos
    $request_viejo = $request->all();
    $request = Conversiones::checkArray($request);
    $diferencia = array_diff($request, $request_viejo);
    if([] != $diferencia)
    {
      return Response::json("Caracteres especiales no soportados",400);
    }

    //Revisar si hay token
    if(!isset($request['token']))
    {
      return Response::json('Token no enviado',400);
    }

    $token = $request['token'];

    //Buscar token
    $count = DB::select("SELECT COUNT(Token) as numero FROM dbo.[WebToken] WHERE Token = '$token' AND GETDATE() < FechaCaducidad");

    $count = $count[0]->numero;

    //Validar token
    if($count < 1)
    {
      return Response::json('Token no valido',400);
    }

    //Buscar parametros necesarios
    if(!(isset($request['codigo']) && isset($request['apellido']) && isset($request['autorizacion'])))
    {
      return Response::json('Parametros necesarios no mandados',400);
    }

    $autorizacion = $request['autorizacion'];
    $codigo = $request['codigo'];
    $apellido = $request['apellido'];

    //Buscar Reserva
    $reserva = DB::select("SELECT  WebSIPPCode.Descripcion,WebReservasTMP.* FROM WebReservasTMP,WebSIPPCode
    WHERE WebReservasTMP.IDReservacion = '$codigo' AND WebReservasTMP.ApellidoPaterno = '$apellido' AND WebSIPPCode.IDSIPPCode = WebReservasTMP.IDSIPPCode");

    //Validar que encontramos reserva
    if(null == $reserva)
    {
      return Response::json('reserva no encontrada',500);
    }

    //Buscamos si la autorizacion ya existe
    $numero_aut = DB::select("SELECT * FROM WebReservas WHERE TC_Aut = '$autorizacion'");
    if($numero_aut != null)
    {
      return Response::json('Esa autorizacion ya ha sido utilizada',500);
    }

    try {
        // Configurar la clave secreta de Stripe
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $paymentIntent = PaymentIntent::retrieve($autorizacion);
        // Puedes acceder a propiedades del PaymentIntent, como el estado, monto, etc.
        // echo json_encode($paymentIntent);
        // Verificar el estado, la moneda y el monto
        $monto_pagado = ((float)$paymentIntent->amount_received/100); // Monto recibido en centavos
        $total_temp_temp = (float)$reserva[0]->Total;
        $currency = $paymentIntent->currency; //Moneda del pago
        $status = $paymentIntent->status; // Estado del PaymentIntent
        //Establecemos la moneda a pagar
        $moneda = 'mxn';
        if('USD' == $reserva[0]->Moneda)
        {
          $moneda = 'usd';
        }
        if('MN' == $reserva[0]->Moneda)
        {
          $moneda = 'mxn';
        }

        //Verificamos la que la moneda sea igual
        if($currency != $moneda)
        {
          return Response::json('Monedas a pagar diferentes',500);
        }

        //Checamos el monto a pagar
        if (abs($total_temp_temp - $monto_pagado) > 1)
        {
          return Response::json('No ha pagado lo suficiente',500);
        }

        if('succeeded' != $status)
        {
          return Response::json('No se ha completado el pagado',500);
        }


    } catch (\Stripe\Exception\ApiErrorException $e) {
        // Maneja el error
        return Response::json('Autorizacion no valida',500);
    }

    //Poner la autorizacion
    $update = DB::update("UPDATE WebReservasTMP SET MetodoPago = 'Stripe', FormaPago = 'Tarjeta', TC_Aut = '$autorizacion',MontoPagado = $monto_pagado WHERE IDReservacion = '$codigo' AND ApellidoPaterno = '$apellido';");

    $move = DB::insert("SET DATEFORMAT MDY;
    INSERT INTO WebReservas SELECT [IDReservacion]
          ,[TipoReservacion]
          ,[TipoMovRegistro]
          ,[IDPCode]
          ,[IDTarifa]
          ,[IDAgencia]
          ,[IDComisionista]
          ,[IDSIPPCode]
          ,[IDCodDescuento]
          ,[IDCteLeal]
          ,[IDPCS]
          ,[FechaHoraOperacion]
          ,[FechaControl]
          ,[OrigenReservacion]
          ,[Status]
          ,[ApellidoPaterno]
          ,[ApellidoMaterno]
          ,[Nombre]
          ,[NombreCompleto]
          ,[Moneda]
          ,[TDCaplicado]
          ,[IDOficinaReservacion]
          ,[FechaReservacion]
          ,[HoraReservacion]
          ,[IDOficinaRetorno]
          ,[FechaRetorno]
          ,[HoraRetorno]
          ,[IDPlazaReservacion]
          ,[IDPlazaRetorno]
          ,[DiasRenta]
          ,[HorasRenta]
          ,[DiasXMes]
          ,[DiasXSemana]
          ,[TarifaMesTTI]
          ,[TarifaMesTK]
          ,[CantidadMes]
          ,[ImporteMesTK]
          ,[TarifaSemanaTTI]
          ,[TarifaSemanaTK]
          ,[CantidadSemana]
          ,[ImporteSemanaTK]
          ,[TarifaDiaExtraTTI]
          ,[TarifaDiaExtraTK]
          ,[CantidadDiaExtra]
          ,[ImporteDiaExtraTK]
          ,[TarifaDiaTTI]
          ,[TarifaDiaTK]
          ,[CantidadDia]
          ,[ImporteDiaTK]
          ,[TarifaFinSemanaTTI]
          ,[TarifaFinSemanaTK]
          ,[CantidadDiaFinSemana]
          ,[ImporteDiaFinSemanaTK]
          ,[TarifaHoraTTI]
          ,[TarifaHoraTK]
          ,[CantidadHora]
          ,[ImporteHoraTK]
          ,[ImporteDescuentoTK]
          ,[TotalTK]
          ,[BanderaPrepago]
          ,[TitDP]
          ,[CostoDiaDP]
          ,[BanderaDP]
          ,[ImporteDP]
          ,[TitCDW]
          ,[CostoDiaCDW]
          ,[BanderaCDW]
          ,[ImporteCDW]
          ,[PorcentajeDeducibleRobo]
          ,[PorcentajeDeducibleDanos]
          ,[DeducibleCDW]
          ,[TitPAI]
          ,[CostoDiaPAI]
          ,[BanderaPAI]
          ,[ImportePAI]
          ,[TextoMontoMaximoPAI]
          ,[TitPLI]
          ,[CostoDiaPLI]
          ,[BanderaPLI]
          ,[ImportePLI]
          ,[MontoMaximoPLI]
          ,[TitPLIA]
          ,[CostoDiaPLIA]
          ,[BanderaPLIA]
          ,[ImportePLIA]
          ,[MontoMaximoPLIA]
          ,[TitMDW]
          ,[CostoDiaMDW]
          ,[BanderaMDW]
          ,[ImporteMDW]
          ,[MontoMaximoMDW]
          ,[TotalCoberturas]
          ,[TitERA]
          ,[CostoDiaERA]
          ,[BanderaERA]
          ,[CantidadERA]
          ,[ImporteERA]
          ,[TitETS]
          ,[CostoDiaETS]
          ,[BanderaETS]
          ,[CantidadETS]
          ,[ImporteETS]
          ,[TitCA]
          ,[CostoDiaCA]
          ,[BanderaCA]
          ,[CantidadCA]
          ,[ImporteCA]
          ,[TitBS1]
          ,[CostoDiaBS1]
          ,[BanderaBS1]
          ,[CantidadBS1]
          ,[ImporteBS1]
          ,[TitBS2]
          ,[CostoDiaBS2]
          ,[BanderaBS2]
          ,[CantidadBS2]
          ,[ImporteBS2]
          ,[TitBS3]
          ,[CostoDiaBS3]
          ,[BanderaBS3]
          ,[CantidadBS3]
          ,[ImporteBS3]
          ,[TitCM]
          ,[CostoDiaCM]
          ,[BanderaCM]
          ,[CantidadCM]
          ,[ImporteCM]
          ,[TitGPS]
          ,[CostoDiaGPS]
          ,[BanderaGPS]
          ,[CantidadGPS]
          ,[ImporteGPS]
          ,[CargoPickup]
          ,[ComentarioPickup]
          ,[CargoDropOff]
          ,[TotalExtras]
          ,[BanderaLCRFee]
          ,[CargoLCRFee]
          ,[BanderaSCFee]
          ,[CargoSCFee]
          ,[AplicacionCCFee]
          ,[BanderaCCFee]
          ,[CargoCCFee]
          ,[BanderaAirportFee]
          ,[PorAirportFee]
          ,[MontoAirportFee]
          ,[BanderaHotelFee]
          ,[PorHotelFee]
          ,[MontoHotelFee]
          ,[ImporteDescuento]
          ,[ImporteDescuentoPpgo]
          ,[Subtotal]
          ,[BanderaIVA]
          ,[PorIva]
          ,[MontoIva]
          ,[Total]
          ,[TotalOrigen]
          ,[Comentarios]
          ,[MensajeEstimado]
          ,[Email]
          ,[MontoPagado]
          ,[SerieCFD]
          ,[FolioCFD]
          ,[FechaCFD]
          ,[MetodoPago]
          ,[FormaPago]
          ,[CuentaPago]
          ,[Banco]
          ,[TipoTdC]
          ,[NumeroTdC]
          ,[CodSegTdC]
          ,[TC_Expira]
          ,[TC_Aut]
          ,[Dom_Local]
          ,[Dom_Permanente]
          ,[Edad]
          ,[Telefono]
          ,[Con_Lic]
          ,[Con_Lic_Expira]
          ,[Con_Lic_Edo]
          ,[CteVIP]
          ,[Aerolinea]
          ,[Vuelo]
          ,[StatusEnvioMail]
          ,[FechaLecturaJR]
          ,[FechaEnvioMail] FROM WebReservasTMP WHERE IDReservacion = '$codigo' AND ApellidoPaterno = '$apellido';");

    if(true == $move)
    {
      $eliminar = DB::delete("DELETE FROM WebReservasTMP WHERE IDReservacion = '$codigo' AND ApellidoPaterno = '$apellido';");

      return Response::json(true,200);
    }
    else
    {
      return Response::json('No se pudo mover el registro, o no se encontro',500);
    }

    return Response::json(true,200);
  }

  public function cancelarReserva(Request $request)
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
          if(isset($request['codigo']) && isset($request['apellido']))
          {
            $codigo = $request['codigo'];
            $apellido = $request['apellido'];

            $reserva = DB::select("SELECT  WebSIPPCode.Descripcion,WebReservas.*
                                  FROM WebReservas,WebSIPPCode WHERE WebReservas.IDReservacion = '$codigo' AND WebReservas.ApellidoPaterno = '$apellido' AND WebSIPPCode.IDSIPPCode = WebReservas.IDSIPPCode");
            if(null == $reserva)
            {
              return Response::json('Reserva no encontrada',500);
            }
            $reserva = $reserva[0];

            if($reserva->MontoPagado == 0 && $reserva->BanderaPrepago == 0)
            {
              $update = DB::update("UPDATE WebReservas SET TipoMovRegistro = 'C',FechaHoraOperacion = GETDATE(), Status = 'Cancelada',FechaLecturaJR = null WHERE IDReservacion = '$codigo' AND ApellidoPaterno = '$apellido';");

              if($update >= 1)
              {
                return Response::json('Reserva Cancelada',201);
              }
              else
              {
                return Response::json('Error al cancelar la reserva',500);
              }
            }
            else
            {
              return Response::json('La reserva no se puede cancelar porque es prepago o ya se pago algo de dinero',400);
            }
          }
          else
          {
            return Response::json('Parametros no encontrados por el verbo GET',400);
          }
        }
      }
    }
    return Response::json('token no valido',409);
  }

  public function moverCotizacion(Request $request)
  {
    //Revisar la entrada de datos
    $request_viejo = $request->all();
    $request = Conversiones::checkArray($request);
    $diferencia = array_diff($request, $request_viejo);
    if([] != $diferencia)
    {
      return Response::json("Caracteres especiales no soportados",400);
    }

    //Revisar si hay token
    if(!isset($request['token']))
    {
      return Response::json('Token no enviado',400);
    }

    $token = $request['token'];

    //Buscar token
    $count = DB::select("SELECT COUNT(Token) as numero FROM dbo.[WebToken] WHERE Token = '$token' AND GETDATE() < FechaCaducidad");

    $count = $count[0]->numero;

    //Validar token
    if($count < 1)
    {
      return Response::json('Token no valido',400);
    }

    //Buscar parametros necesarios
    if(!isset($request['codigo']))
    {
      return Response::json('Parametros necesarios no mandados',400);
    }

    //Checar si el codigo ya ha sido usado
    $codigo = $request['codigo'];
    $codigo_usado = DB::select("SELECT IDReservacion FROM WebReservas WHERE IDReservacion = '$codigo'");
    if($codigo_usado != null)
    {
      return Response::json('El codigo ya ha sido usado para una reservacion');
    }

    //Buscar Reserva
    $cotizacion = DB::select("SELECT BanderaPrepago,ApellidoPaterno,Email FROM WebReservasTMP WHERE IDReservacion = '$codigo' ");

    //Validar que encontramos reserva
    if(null == $cotizacion)
    {
      return Response::json('Cotizacion no encontrada',500);
    }

    $cotizacion = $cotizacion[0];

    if($cotizacion->BanderaPrepago == '1')
    {
      return Response::json('Para que esta cotizacion sea una reserva se debe realizar con el webservice activarPrepago pues esta cotizacion es prepagada',400);
    }

    if($cotizacion->ApellidoPaterno == null)
    {
      return Response::json('Favor de insertar un apellido paterno',400);
    }

    //Movemos la reserva
    $move = DB::insert("SET DATEFORMAT MDY;
    INSERT INTO WebReservas SELECT [IDReservacion]
          ,[TipoReservacion]
          ,[TipoMovRegistro]
          ,[IDPCode]
          ,[IDTarifa]
          ,[IDAgencia]
          ,[IDComisionista]
          ,[IDSIPPCode]
          ,[IDCodDescuento]
          ,[IDCteLeal]
          ,[IDPCS]
          ,[FechaHoraOperacion]
          ,[FechaControl]
          ,[OrigenReservacion]
          ,[Status]
          ,[ApellidoPaterno]
          ,[ApellidoMaterno]
          ,[Nombre]
          ,[NombreCompleto]
          ,[Moneda]
          ,[TDCaplicado]
          ,[IDOficinaReservacion]
          ,[FechaReservacion]
          ,[HoraReservacion]
          ,[IDOficinaRetorno]
          ,[FechaRetorno]
          ,[HoraRetorno]
          ,[IDPlazaReservacion]
          ,[IDPlazaRetorno]
          ,[DiasRenta]
          ,[HorasRenta]
          ,[DiasXMes]
          ,[DiasXSemana]
          ,[TarifaMesTTI]
          ,[TarifaMesTK]
          ,[CantidadMes]
          ,[ImporteMesTK]
          ,[TarifaSemanaTTI]
          ,[TarifaSemanaTK]
          ,[CantidadSemana]
          ,[ImporteSemanaTK]
          ,[TarifaDiaExtraTTI]
          ,[TarifaDiaExtraTK]
          ,[CantidadDiaExtra]
          ,[ImporteDiaExtraTK]
          ,[TarifaDiaTTI]
          ,[TarifaDiaTK]
          ,[CantidadDia]
          ,[ImporteDiaTK]
          ,[TarifaFinSemanaTTI]
          ,[TarifaFinSemanaTK]
          ,[CantidadDiaFinSemana]
          ,[ImporteDiaFinSemanaTK]
          ,[TarifaHoraTTI]
          ,[TarifaHoraTK]
          ,[CantidadHora]
          ,[ImporteHoraTK]
          ,[ImporteDescuentoTK]
          ,[TotalTK]
          ,[BanderaPrepago]
          ,[TitDP]
          ,[CostoDiaDP]
          ,[BanderaDP]
          ,[ImporteDP]
          ,[TitCDW]
          ,[CostoDiaCDW]
          ,[BanderaCDW]
          ,[ImporteCDW]
          ,[PorcentajeDeducibleRobo]
          ,[PorcentajeDeducibleDanos]
          ,[DeducibleCDW]
          ,[TitPAI]
          ,[CostoDiaPAI]
          ,[BanderaPAI]
          ,[ImportePAI]
          ,[TextoMontoMaximoPAI]
          ,[TitPLI]
          ,[CostoDiaPLI]
          ,[BanderaPLI]
          ,[ImportePLI]
          ,[MontoMaximoPLI]
          ,[TitPLIA]
          ,[CostoDiaPLIA]
          ,[BanderaPLIA]
          ,[ImportePLIA]
          ,[MontoMaximoPLIA]
          ,[TitMDW]
          ,[CostoDiaMDW]
          ,[BanderaMDW]
          ,[ImporteMDW]
          ,[MontoMaximoMDW]
          ,[TotalCoberturas]
          ,[TitERA]
          ,[CostoDiaERA]
          ,[BanderaERA]
          ,[CantidadERA]
          ,[ImporteERA]
          ,[TitETS]
          ,[CostoDiaETS]
          ,[BanderaETS]
          ,[CantidadETS]
          ,[ImporteETS]
          ,[TitCA]
          ,[CostoDiaCA]
          ,[BanderaCA]
          ,[CantidadCA]
          ,[ImporteCA]
          ,[TitBS1]
          ,[CostoDiaBS1]
          ,[BanderaBS1]
          ,[CantidadBS1]
          ,[ImporteBS1]
          ,[TitBS2]
          ,[CostoDiaBS2]
          ,[BanderaBS2]
          ,[CantidadBS2]
          ,[ImporteBS2]
          ,[TitBS3]
          ,[CostoDiaBS3]
          ,[BanderaBS3]
          ,[CantidadBS3]
          ,[ImporteBS3]
          ,[TitCM]
          ,[CostoDiaCM]
          ,[BanderaCM]
          ,[CantidadCM]
          ,[ImporteCM]
          ,[TitGPS]
          ,[CostoDiaGPS]
          ,[BanderaGPS]
          ,[CantidadGPS]
          ,[ImporteGPS]
          ,[CargoPickup]
          ,[ComentarioPickup]
          ,[CargoDropOff]
          ,[TotalExtras]
          ,[BanderaLCRFee]
          ,[CargoLCRFee]
          ,[BanderaSCFee]
          ,[CargoSCFee]
          ,[AplicacionCCFee]
          ,[BanderaCCFee]
          ,[CargoCCFee]
          ,[BanderaAirportFee]
          ,[PorAirportFee]
          ,[MontoAirportFee]
          ,[BanderaHotelFee]
          ,[PorHotelFee]
          ,[MontoHotelFee]
          ,[ImporteDescuento]
          ,[ImporteDescuentoPpgo]
          ,[Subtotal]
          ,[BanderaIVA]
          ,[PorIva]
          ,[MontoIva]
          ,[Total]
          ,[TotalOrigen]
          ,[Comentarios]
          ,[MensajeEstimado]
          ,[Email]
          ,[MontoPagado]
          ,[SerieCFD]
          ,[FolioCFD]
          ,[FechaCFD]
          ,[MetodoPago]
          ,[FormaPago]
          ,[CuentaPago]
          ,[Banco]
          ,[TipoTdC]
          ,[NumeroTdC]
          ,[CodSegTdC]
          ,[TC_Expira]
          ,[TC_Aut]
          ,[Dom_Local]
          ,[Dom_Permanente]
          ,[Edad]
          ,[Telefono]
          ,[Con_Lic]
          ,[Con_Lic_Expira]
          ,[Con_Lic_Edo]
          ,[CteVIP]
          ,[Aerolinea]
          ,[Vuelo]
          ,[StatusEnvioMail]
          ,[FechaLecturaJR]
          ,[FechaEnvioMail] FROM WebReservasTMP WHERE IDReservacion = '$codigo';");

    if(true == $move)
    {
      return Response::json(true,200);
    }
    else
    {
      return Response::json('No se pudo mover el registro, o no se encontro',500);
    }

    return Response::json(true,200);
  }
}
