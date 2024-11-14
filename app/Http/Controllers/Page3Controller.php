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


class Page3Controller extends Controller
{
    //Optimizado
    public function getSoloSeguros(Request $request)
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
      //Cachamos parametros
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

      if(isset($request['Edad']))
      {
        $edad = $request['Edad'];
      }
      else
      {
        $edad = 18;
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

      //Seguros
      $seguros = ['DP'=> $dp,'CDW'=> $cdw,'PAI'=> $pai,'PLI'=> $pli,'PLIA'=> $plia,'Prepago'=>$prepago,
      'MDW'=> $mdw,'ERA'=> $era,'ETS'=> $ets,'CA'=> $ca,'BS1'=> $bs1,'BS2'=> $bs2,'BS3'=> $bs3,
      'CM'=> $cm,'GPS'=> $gps,'LCRF'=>1,'AF'=>1,'HF'=>1,'SCF'=>1];

      //Cachamos algunas otras variables
      $sipp_code = $request['SIPPCode'];
      $id_oficina = $request['id'];
      $id_oficina_retorno = $request['id_retorno'];

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

      //Obtenemos los titulos de los seguros de la configuracion general
      $seguros_tit = DB::select("SELECT TitCDW, TitDP, TitPAI, TitPLI, TitPLIA, TitMDW, TitERA, TitETS, TitCA, TitBS1, TitBS2, TitBS3, TitCM, TitGPS FROM dbo.WebConfiguracionGral");
      if($seguros_tit == null)
      {
        return Response::Json('No se encontraron seguros');
      }
      $seguros_tit = $seguros_tit[0];

      //Encontramos la oficina con el id la igual que la de retorno
      $oficina = DB::select("SELECT IDPlaza,IDTarifa,PorIVA FROM dbo.[WebOficina] WHERE IDOficina = '$id_oficina'");
      $oficina_regreso = DB::select("SELECT IDPlaza FROM dbo.[WebOficina] WHERE IDOficina = '$id_oficina_retorno'");
      if(null == $oficina || null == $oficina_regreso)
      {
        return Response::json('Oficina no encontrada', 500);
      }

      //Cachamos datos de la oficina de salida
      $oficina = $oficina[0];
      $iva = 1 + ($oficina->PorIVA/100);
      $id_plaza = $oficina->IDPlaza;

      //Cachamos datos de la oficina de regreso
      $id_plaza_regreso = $oficina_regreso[0]->IDPlaza;

      //Obtenemos datos de configuracion de la configuracion
      $configuracion = DB::select("SELECT DiasXMes, DiasXSem, HorasxDia,PorDescuentoPpgo FROM dbo.WebConfiguracionGral");
      if($configuracion == null)
      {
        return Response::json('Configuracion no encontrada', 500);
      }
      $configuracion = $configuracion[0];

      //Buscamos el auto que nos mandaron
      $grupo_auto = DB::select("SELECT b.Descripcion,a.IDSIPPCode1 FROM dbo.[WebGrupoAutos] a LEFT JOIN dbo.[WebSIPPCode] b ON  a.IDSIPPCode1 = b.IDSIPPCode
                                        WHERE a.IDPlaza = $id_plaza AND a.Activo = 1 AND a.IDSIPPCode1 = '$sipp_code'  ORDER BY b.Ordenamiento");
      if($grupo_auto == null)
      {
        return Response::json('Carro no encontrado', 500);
      }
      $grupo_auto = $grupo_auto[0];

      //Obtenemos el tiempo segun las fechas
      $tiempo  = Conversiones::getDaysHours($request['fecha_inicial'],$request['fecha_final']);
      if(gettype($tiempo) == 'integer')
      {
        return Response::json('Fechas Invalidas',400);
      }

      //Cachamos las variables del tiempo
      $fecha = $tiempo->fecha_inicial;
      $es_fin_semana = $tiempo->es_fin_semana;
      $dias = $tiempo->dias;
      $horas = $tiempo->horas;

      //Obtenemos las Tarifas e la fecha y con el IDTarifa obtenido
      $tarifas_filtradas =  Calculos::getTarifas($fecha,$id_promocion,$id_cliente,$id_agencia,$id_comisionista,$id_oficina,$id_plaza,$sub_tarifa);

      //Le colocamos su descripcion y SIPPCode
      $auto = new stdClass();
      $auto->descripcion = $grupo_auto->Descripcion;
      $auto->SIPPCode = $grupo_auto->IDSIPPCode1;

      //Validar Promocion
      $promocion = Validaciones::validarCodigoDescuento($id_promocion,$tiempo->fecha_inicial,$tiempo->fecha_final,$tiempo->dias,$id_plaza,$id_oficina,$fecha,$auto->SIPPCode,$id_agencia,$id_comisionista);;

      //Obtener la cotizacion del auto y seguros
      $auto = Calculos::getPrecio($id_cliente,$id_agencia,$id_comisionista,$id_oficina,$id_plaza,$id_plaza_regreso,$es_fin_semana,$configuracion,$promocion,$iva,$edad,$fecha,$tiempo,$tarifas_filtradas,$auto,$seguros);

      //Llenaremos los campos de seguros
      $seguros_json = [];

      $PLI = new stdClass();
      $PLI->seguro = 'PLI';
      $PLI->titulo = $seguros_tit->TitPLI;
      $PLI->costo = $auto->costo->seguros->PLI_costo;
      $PLI->es_forsozo = $auto->costo->seguros->PLI_bandera;
      $PLI->venta = $auto->costo->seguros->ventaPLI;
      $PLI->es_multiple = 0;
      $PLI->dias = $dias;
      array_push($seguros_json,$PLI);

      $CDW = new stdClass();
      $CDW->seguro = 'CDW';
      $CDW->titulo = $seguros_tit->TitCDW;
      $CDW->costo = $auto->costo->seguros->CDW_costo;
      $CDW->es_forsozo = $auto->costo->seguros->CDW_bandera;
      $CDW->venta = $auto->costo->seguros->ventaCDW;
      $CDW->es_multiple = 0;
      $CDW->dias = $dias;
      array_push($seguros_json,$CDW);

      $DP = new stdClass();
      $DP->seguro = 'DP';
      $DP->titulo = $seguros_tit->TitDP;
      $DP->costo = $auto->costo->seguros->DP_costo;
      $DP->es_forsozo = $auto->costo->seguros->DP_bandera;
      $DP->venta = $auto->costo->seguros->ventaDP;
      $DP->es_multiple = 0;
      $DP->dias = $dias;
      array_push($seguros_json,$DP);

      $PAI = new stdClass();
      $PAI->seguro = 'PAI';
      $PAI->titulo = $seguros_tit->TitPAI;
      $PAI->costo = $auto->costo->seguros->PAI_costo;
      $PAI->es_forsozo = $auto->costo->seguros->PAI_bandera;
      $PAI->venta = $auto->costo->seguros->ventaPAI;
      $PAI->es_multiple = 0;
      $PAI->dias = $dias;
      array_push($seguros_json,$PAI);

      $PLIA = new stdClass();
      $PLIA->seguro = 'PLIA';
      $PLIA->titulo = $seguros_tit->TitPLIA;
      $PLIA->costo = $auto->costo->seguros->PLIA_costo;
      $PLIA->es_forsozo = $auto->costo->seguros->PLIA_bandera;
      $PLIA->venta = $auto->costo->seguros->ventaPLIA;
      $PLIA->es_multiple = 0;
      $PLIA->dias = $dias;
      array_push($seguros_json,$PLIA);

      $MDW = new stdClass();
      $MDW->seguro = 'MDW';
      $MDW->titulo = $seguros_tit->TitMDW;
      $MDW->costo = $auto->costo->seguros->MDW_costo;
      $MDW->es_forsozo = $auto->costo->seguros->MDW_bandera;
      $MDW->venta = $auto->costo->seguros->ventaMDW;
      $MDW->es_multiple = 0;
      $MDW->dias = $dias;
      array_push($seguros_json,$MDW);

      $CA = new stdClass();
      $CA->seguro = 'CA';
      $CA->titulo = $seguros_tit->TitCA;
      $CA->costo = $auto->costo->seguros->CA_costo;
      $CA->es_forsozo = $auto->costo->seguros->CA_bandera;
      $CA->venta = 1;
      $CA->es_multiple = 1;
      $CA->dias = $dias;
      array_push($seguros_json,$CA);

      $BS1 = new stdClass();
      $BS1->seguro = 'BS1';
      $BS1->titulo = $seguros_tit->TitBS1;
      $BS1->costo = $auto->costo->seguros->BS1_costo;
      $BS1->es_forsozo = $auto->costo->seguros->BS1_bandera;
      $BS1->venta = 1;
      $BS1->es_multiple = 1;
      $BS1->dias = $dias;
      array_push($seguros_json,$BS1);

      $BS2 = new stdClass();
      $BS2->seguro = 'BS2';
      $BS2->titulo = $seguros_tit->TitBS2;
      $BS2->costo = $auto->costo->seguros->BS2_costo;
      $BS2->es_forsozo = $auto->costo->seguros->BS2_bandera;
      $BS2->venta = 1;
      $BS2->es_multiple = 1;
      $BS2->dias = $dias;
      array_push($seguros_json,$BS2);

      $BS3 = new stdClass();
      $BS3->seguro = 'BS3';
      $BS3->titulo = $seguros_tit->TitBS3;
      $BS3->costo = $auto->costo->seguros->BS3_costo;
      $BS3->es_forsozo = $auto->costo->seguros->BS3_bandera;
      $BS3->venta = 1;
      $BS3->es_multiple = 1;
      $BS3->dias = $dias;
      array_push($seguros_json,$BS3);

      $ERA = new stdClass();
      $ERA->seguro = 'ERA';
      $ERA->titulo = $seguros_tit->TitERA;
      $ERA->costo = $auto->costo->seguros->ERA_costo;
      $ERA->es_forsozo = $auto->costo->seguros->ERA_bandera;
      $ERA->venta = 1;
      $ERA->es_multiple = 0;
      $ERA->dias = $dias;
      array_push($seguros_json,$ERA);

      $ETS = new stdClass();
      $ETS->seguro = 'ETS';
      $ETS->titulo = $seguros_tit->TitETS;
      $ETS->costo = $auto->costo->seguros->ETS_costo;
      $ETS->es_forsozo = $auto->costo->seguros->ETS_bandera;
      $ETS->venta = 1;
      $ETS->es_multiple = 0;
      $ETS->dias = $dias;
      array_push($seguros_json,$ETS);

      $CM = new stdClass();
      $CM->seguro = 'CM';
      $CM->titulo = $seguros_tit->TitCM;
      $CM->costo = $auto->costo->seguros->CM_costo;
      $CM->es_forsozo = $auto->costo->seguros->CM_bandera;
      $CM->venta = 1;
      $CM->es_multiple = 1;
      $CM->dias = $dias;
      array_push($seguros_json,$CM);

      $GPS = new stdClass();
      $GPS->seguro = 'GPS';
      $GPS->titulo = $seguros_tit->TitGPS;
      $GPS->costo = $auto->costo->seguros->GPS_costo;
      $GPS->es_forsozo = $auto->costo->seguros->GPS_bandera;
      $GPS->venta = 1;
      $GPS->es_multiple = 1;
      $GPS->dias = $dias;
      array_push($seguros_json,$GPS);

      //Los fees por el momento no van
      // $LCRF = new stdClass();
      // $LCRF->titulo = 'LCRF';//$seguros_tit->TitLCRF;
      // $LCRF->costo = $auto->costo->seguros->LCRF;
      // $LCRF->es_forsozo = $auto->costo->seguros->LCRF_bandera;
      // $LCRF->es_multiple = 0;
      // array_push($seguros_json,$LCRF);
      //
      // $AF = new stdClass();
      // $AF->titulo = 'AF';//$seguros_tit->TitAF;
      // $AF->costo = $auto->costo->seguros->AF;
      // $AF->es_forsozo = $auto->costo->seguros->AF_bandera;
      // $AF->es_multiple = 0;
      // array_push($seguros_json,$AF);
      //
      // $HF = new stdClass();
      // $HF->titulo = 'HF';//$seguros_tit->TitHF;
      // $HF->costo = $auto->costo->seguros->HF;
      // $HF->es_forsozo = $auto->costo->seguros->HF_bandera;
      // $HF->es_multiple = 0;
      // array_push($seguros_json,$HF);
      //
      // $SCF = new stdClass();
      // $SCF->titulo = 'SCF';//$seguros_tit->TitSCF;
      // $SCF->costo = $auto->costo->seguros->SCF;
      // $SCF->es_forsozo = $auto->costo->seguros->SCF_bandera;
      // $SCF->es_multiple = 0;
      // array_push($seguros_json,$SCF);

      //Agregamos moneda
      $moneda = new stdClass();
      $moneda->moneda = $auto->Moneda;
      array_push($seguros_json,$moneda);

      //Retornamos los seguros del auto
      return Response::json($seguros_json,201);
    }

    public function guardarCotizacion(Request $request)
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
      && isset($request['codigo']) && isset($request['Prepago'])))
      {
        return Response::json('Parametros no mandados correctamente',400);
      }

      //Checar si el codigo ya ha sido usado
      $codigo = $request['codigo'];
      $codigo_usado = DB::select("SELECT IDReservacion FROM WebReservas WHERE IDReservacion = '$codigo'");
      if($codigo_usado != null)
      {
        return Response::json('El codigo ya ha sido usado para una reservacion');
      }

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

      if(isset($request['nombre']))
      {
        $nombre = $request['nombre'];
      }
      else
      {
        $nombre = NULL;
      }

      if(isset($request['materno']))
      {
        $materno = $request['materno'];
      }
      else
      {
        $materno = NULL;
      }

      if(isset($request['paterno']))
      {
        $paterno = $request['paterno'];
      }
      else
      {
        $paterno = NULL;
      }

      if(isset($request['email']))
      {
        $email = $request['email'];
      }
      else
      {
        $email = NULL;
      }

      //Obtener parametros obligatorios
      $prepago = $request['Prepago'];
      $sipp_code = $request['SIPPCode'];
      $id_oficina = $request['id'];
      $id_oficina_retorno = $request['id_retorno'];
      $seguros = ['DP'=> $dp,'CDW'=> $cdw,'PAI'=> $pai,'PLI'=> $pli,'PLIA'=> $plia,'Prepago'=>$prepago,
      'MDW'=> $mdw,'ERA'=> $era,'ETS'=> $ets,'CA'=> $ca,'BS1'=> $bs1,'BS2'=> $bs2,'BS3'=> $bs3,
      'CM'=> $cm,'GPS'=> $gps,'LCRF'=>1,'AF'=>1,'HF'=>1,'SCF'=>1];

      $nombre_completo = $nombre.' '.$paterno.' '.$materno;

      //Checar si ya existe la cotizacion
      $cotizacion = DB::select("SELECT IDReservacion FROM WebReservasTMP WHERE IDReservacion = '$codigo'");
      //Si si existe la eliminamos para hacer otra
      if(null != $cotizacion)
      {
        $eliminar = DB::delete("DELETE FROM WebReservasTMP WHERE IDReservacion = '$codigo'");
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
          $auto_nuevo->Sippcode = $tarifa->IDSIPPCode;
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
      }
      else
      {
        $costo->promocion_prepago = 0;
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
         ,$configuracion->DiasXMes
         ,$configuracion->DiasXSem
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
      if(true != $insercion)
      {
        return Response::json('No se pudo guardar la cotizacion',201);
      }
      $reserva = DB::select("SELECT  WebSIPPCode.Descripcion,WebReservasTMP.*
                            FROM WebReservasTMP,WebSIPPCode WHERE WebReservasTMP.IDReservacion = '$codigo' AND WebReservasTMP.IDSIPPCode = WebReservasTMP.IDSIPPCode");

      if(null == $reserva)
      {
        return Response::json('Insercion fallida',201);
      }

      $auto->Nombre = $nombre;
      $auto->ApellidoPaterno = $paterno;
      $auto->ApellidoMaterno = $materno;
      $auto->NombreCompleto = $nombre.' '.$paterno.' '.$materno;
      $auto->Telefono = $telefono;
      $auto->Email = $email;
      $auto->Con_Lic = $licencia;
      $auto->Con_Lic_Expira = $licencia_expira;
      $auto->Con_Lic_Edo = $licencia_estado;
      $auto->Aerolinea = $aerolinea;
      $auto->Vuelo = $vuelo;
      return Response::json($auto,201);
    }

    public function modificarCotizacion(Request $request)
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
      if(!isset($request['codigo']))
      {
        return Response::json('Parametros no mandados correctamente',400);
      }

      $codigo = $request['codigo'];

      //Checar si el codigo ha sido usado
      $codigo_usado = DB::select("SELECT IDReservacion FROM WebReservas WHERE IDReservacion = '$codigo'");
      if($codigo_usado != null)
      {
        return Response::json('El codigo ya ha sido usado para una reservacion');
      }

      //Checar si ya existe la cotizacion
      $cotizacion = DB::select("SELECT * FROM WebReservasTMP WHERE IDReservacion = '$codigo'");
      //Si no existe la eliminamos para hacer otra
      if(null == $cotizacion)
      {
        return Response::json('Cotizacion no encontrada, favor de primero guardar la cotizacion',400);
      }
      $cotizacion = $cotizacion[0];




      //Seguros
      if(isset($request['DP']))
      {
          $dp = $request['DP'];
      }
      else
      {
          $dp = $cotizacion->BanderaDP;
      }

      if(isset($request['CDW']))
      {
          $cdw = $request['CDW'];
      }
      else
      {
          $cdw = $cotizacion->BanderaCDW;
      }

      if(isset($request['PAI']))
      {
          $pai = $request['PAI'];
      }
      else
      {
          $pai = $cotizacion->BanderaPAI;
      }

      if(isset($request['PLI']))
      {
          $pli = $request['PLI'];
      }
      else
      {
          $pli = $cotizacion->BanderaPLI;
      }

      if(isset($request['PLIA']))
      {
        $plia = $request['PLIA'];
      }
      else
      {
        $plia = $cotizacion->BanderaPLIA;
      }

      if(isset($request['MDW']))
      {
        $mdw = $request['MDW'];
      }
      else
      {
        $mdw = $cotizacion->BanderaMDW;
      }

      if(isset($request['ERA']))
      {
        $era = $request['ERA'];
      }
      else
      {
        $era = $cotizacion->BanderaERA;
      }

      if(isset($request['ETS']))
      {
        $ets = $request['ETS'];
      }
      else
      {
        $ets = $cotizacion->BanderaETS;
      }

      if(isset($request['CA']))
      {
        $ca = $request['CA'];
      }
      else
      {
        $ca = $cotizacion->BanderaCA;
      }

      if(isset($request['BS1']))
      {
        $bs1 = $request['BS1'];
      }
      else
      {
        $bs1 = $cotizacion->BanderaBS1;
      }

      if(isset($request['BS2']))
      {
        $bs2 = $request['BS2'];
      }
      else
      {
        $bs2 = $cotizacion->BanderaBS2;
      }

      if(isset($request['BS3']))
      {
        $bs3 = $request['BS3'];
      }
      else
      {
        $bs3 = $cotizacion->BanderaBS3;
      }

      if(isset($request['CM']))
      {
        $cm = $request['CM'];
      }
      else
      {
        $cm = $cotizacion->BanderaCM;
      }

      if(isset($request['GPS']))
      {
        $gps = $request['GPS'];
      }
      else
      {
        $gps = $cotizacion->BanderaGPS;
      }

      //Otros datos
      if(isset($request['telefono']))
      {
        $telefono = $request['telefono'];
      }
      else
      {
        $telefono = $cotizacion->Telefono;
      }

      if(isset($request['licencia']))
      {
        $licencia = $request['licencia'];
      }
      else
      {
        $licencia = $cotizacion->Con_Lic;
      }

      if(isset($request['licencia_expira']))
      {
        $licencia_expira = $request['licencia_expira'];
      }
      else
      {
        $licencia_expira = $cotizacion->Con_Lic_Expira;
      }

      if(isset($request['licencia_estado']))
      {
        $licencia_estado = $request['licencia_estado'];
      }
      else
      {
        $licencia_estado = $cotizacion->Con_Lic_Edo;
      }

      if(isset($request['aerolinea']))
      {
        $aerolinea = $request['aerolinea'];
      }
      else
      {
        $aerolinea = $cotizacion->Aerolinea;
      }

      if(isset($request['vuelo']))
      {
        $vuelo = $request['vuelo'];
      }
      else
      {
        $vuelo = $cotizacion->Vuelo;
      }

      if(isset($request['nombre']))
      {
        $nombre = $request['nombre'];
      }
      else
      {
        $nombre = $cotizacion->Nombre;
      }

      if(isset($request['materno']))
      {
        $materno = $request['materno'];
      }
      else
      {
        $materno = $cotizacion->ApellidoMaterno;
      }

      if(isset($request['paterno']))
      {
        $paterno = $request['paterno'];
      }
      else
      {
        $paterno = $cotizacion->ApellidoPaterno;
      }

      if(isset($request['email']))
      {
        $email = $request['email'];
      }
      else
      {
        $email = $cotizacion->Email;
      }

      if(isset($request['edad']))
      {
        $edad = $request['edad'];
      }
      else
      {
        $edad = $cotizacion->Edad;
      }

      //Obtenemos datos de la cotizacion
      $prepago = $cotizacion->BanderaPrepago;
      $id_promocion = $cotizacion->IDCodDescuento;
      $id_oficina = $cotizacion->IDOficinaReservacion;
      $id_oficina_retorno = $cotizacion->IDOficinaRetorno;
      $id_sipp_code = $cotizacion->IDSIPPCode;
      $fecha_inicial = $cotizacion->FechaReservacion;
      $fecha_final = $cotizacion->FechaRetorno;
      $id_tarifa = $cotizacion->IDTarifa;
      $id_cliente = $cotizacion->IDCteLeal;
      $id_agencia = $cotizacion->IDAgencia;
      $id_comisionista = $cotizacion->IDComisionista;
      $precios = new stdClass();
      $precios->mes = $cotizacion->TarifaMesTK;
      $precios->semana = $cotizacion->TarifaSemanaTK;
      $precios->dia_extra = $cotizacion->TarifaDiaExtraTK;
      $precios->dia_fin_semana = $cotizacion->TarifaFinSemanaTK;
      $precios->dia = $cotizacion->TarifaDiaTK;
      $precios->hora = $cotizacion->TarifaHoraTK;

      //Obtener parametros obligatorios
      // $seguros = ['DP'=> $dp,'CDW'=> $cdw,'PAI'=> $pai,'PLI'=> $pli,'PLIA'=> $plia,'Prepago'=>$prepago,
      // 'MDW'=> $mdw,'ERA'=> $era,'ETS'=> $ets,'CA'=> $ca,'BS1'=> $bs1,'BS2'=> $bs2,'BS3'=> $bs3,
      // 'CM'=> $cm,'GPS'=> $gps,'LCRF'=>1,'AF'=>1,'HF'=>1,'SCF'=>1];

      $nombre_completo = $nombre.' '.$paterno.' '.$materno;

      //Buscamos la configuracion general de la empresa
      $configuracion = DB::select("SELECT DiasXMes, DiasXSem, HorasxDia,PorDescuentoPpgo,TitCDW,TitDP,TitPAI,TitPLI,TitPLIA
      ,TitMDW,TitERA,TitETS,TitCA,TitBS1,TitBS2,TitBS3,TitCM,TitGPS FROM dbo.WebConfiguracionGral");
      if($configuracion == null)
      {
        return Response::json('Configuracion no encontrada', 500);
      }
      $configuracion = $configuracion[0];

      $seguros = ['DP'=> $dp,'CDW'=> $cdw,'PAI'=> $pai,
      'PLI'=> $pli,'PLIA'=> $plia,'Prepago'=>$prepago,
      'MDW'=> $mdw,'ERA'=> $era,'ETS'=> $ets,
      'CA'=> $ca,'BS1'=> $bs1,'BS2'=> $bs2,
      'BS3'=> $bs3,'CM'=> $cm,'GPS'=> $gps,'LCRF'=>1,'AF'=>1,'HF'=>1,'SCF'=>1];

      //Obtenemos edad
      if($cotizacion->Edad != null)
      {
        $edad = $cotizacion->Edad;
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
                                        WHERE a.IDPlaza = $id_plaza AND a.Activo = 1 AND a.IDSIPPCode1 = '$id_sipp_code'  ORDER BY b.Ordenamiento");
      if($grupo_auto == null)
      {
        return Response::json('Carro no encontrado', 500);
      }
      $grupo_auto = $grupo_auto[0];

      $tiempo  = Conversiones::getDaysHours($fecha_inicial, $fecha_final);
      if(gettype($tiempo) == 'integer')
      {
        return Response::json('Fechas Invalidas',400);
      }
      $fecha = $tiempo->fecha_inicial;
      $es_fin_semana = $tiempo->es_fin_semana;

      //Le colocamos su descripcion y SIPPCode
      $auto = new stdClass();
      $auto->descripcion = $grupo_auto->Descripcion;
      $auto->SIPPCode = $grupo_auto->IDSIPPCode1;

      $fecha_inicial = $tiempo->fecha_inicial;
      $fecha_final = $tiempo->fecha_final;

      //Validar Promocion
      $promocion = Validaciones::validarCodigoDescuento($id_promocion,$tiempo->fecha_inicial,$tiempo->fecha_final,$tiempo->dias,$id_plaza,$id_oficina,$fecha,$auto->SIPPCode,$id_agencia,$id_comisionista);

      //Encontrar la tarifa con la que se cotizo
      $tarifa = DB::select("SET DATEFORMAT MDY;SELECT * FROM WebTarifasMatriz WHERE IDTarifa = '$cotizacion->IDTarifa' AND IDSIPPCode = '$id_sipp_code' AND FechaMatriz = '$fecha_inicial 12:00:00.000'");
      $tarifa = $tarifa[0];

      //Obtener cotizacion del auto
      $auto = Calculos::getPrecioCotizado($id_cliente,$id_agencia,$id_comisionista,$id_oficina,$id_plaza,$id_plaza_regreso,$es_fin_semana,$configuracion,$promocion,$iva,$edad,$fecha,$tiempo,$tarifa,$auto,$seguros,$precios);
      $seguros = $auto->costo->seguros;
      $costo = $auto->costo;

      if($auto->costo->es_prepago == '1')
      {
        $total = $auto->costo->prepago;
        $monto_iva = $auto->costo->monto_iva_prepago;
        $subtotal = $auto->costo->subtotal_prepago;
        $importe_descuento_prepago = $auto->costo->promocion_prepago;
        $importe_descuento = 0;
      }
      else
      {
        $total = $auto->costo->total;
        $monto_iva = $auto->costo->monto_iva_total;
        $subtotal = $auto->costo->subtotal;
        $importe_descuento_prepago = 0;
        $importe_descuento = $auto->costo->promocion_codigo;
      }

      $update = DB::update("SET DATEFORMAT MDY;
      UPDATE WebReservasTMP
        SET Nombre = '$nombre'
        ,ApellidoPaterno = '$paterno'
        ,ApellidoMaterno = '$materno'
        ,NombreCompleto = '$nombre $paterno $materno'
        ,CostoDiaDP = $seguros->DP/$seguros->dias
        ,BanderaDP = $seguros->DP_bandera
        ,ImporteDP = $seguros->DP
        ,CostoDiaCDW = $seguros->CDW/$seguros->dias
        ,BanderaCDW = $seguros->CDW_bandera
        ,ImporteCDW = $seguros->CDW
        ,CostoDiaPAI = $seguros->PAI/$seguros->dias
        ,BanderaPAI = $seguros->PAI_bandera
        ,ImportePAI = $seguros->PAI
        ,CostoDiaPLI = $seguros->PLI/$seguros->dias
        ,BanderaPLI = $seguros->PLI_bandera
        ,ImportePLI = $seguros->PLI
        ,CostoDiaPLIA = $seguros->PLIA/$seguros->dias
        ,BanderaPLIA = $seguros->PLIA_bandera
        ,ImportePLIA = $seguros->PLIA
        ,CostoDiaMDW = $seguros->MDW/$seguros->dias
        ,BanderaMDW = $seguros->MDW_bandera
        ,ImporteMDW = $seguros->MDW
        ,TotalCoberturas = $seguros->DP + $seguros->CDW + $seguros->PAI + $seguros->PLI + $seguros->PLIA + $seguros->MDW
        ,CostoDiaERA = $seguros->ERA/$seguros->dias
        ,BanderaERA = $seguros->ERA_bandera
        ,CantidadERA = $seguros->ERA_bandera
        ,ImporteERA = $seguros->ERA
        ,CostoDiaETS = $seguros->ETS/$seguros->dias
        ,BanderaETS = $seguros->ETS_bandera
        ,CantidadETS = $seguros->ETS_bandera
        ,ImporteETS = $seguros->ETS
        ,CostoDiaCA = $seguros->CA/$seguros->dias
        ,BanderaCA = $seguros->CA_bandera
        ,CantidadCA = $seguros->CA_bandera
        ,ImporteCA = $seguros->CA
        ,CostoDiaBS1 = $seguros->BS1/$seguros->dias
        ,BanderaBS1 = $seguros->BS1_bandera
        ,CantidadBS1 = $seguros->BS1_bandera
        ,ImporteBS1 = $seguros->BS1
        ,CostoDiaBS2 = $seguros->BS2/$seguros->dias
        ,BanderaBS2 = $seguros->BS2_bandera
        ,CantidadBS2 = $seguros->BS2_bandera
        ,ImporteBS2 = $seguros->BS2
        ,CostoDiaBS3 = $seguros->BS3/$seguros->dias
        ,BanderaBS3 = $seguros->BS3_bandera
        ,CantidadBS3 = $seguros->BS3_bandera
        ,ImporteBS3 = $seguros->BS3
        ,CostoDiaCM = $seguros->CM/$seguros->dias
        ,BanderaCM = $seguros->CM_bandera
        ,CantidadCM = $seguros->CM_bandera
        ,ImporteCM = $seguros->CM
        ,CostoDiaGPS = $seguros->GPS/$seguros->dias
        ,BanderaGPS = $seguros->GPS_bandera
        ,CantidadGPS = $seguros->GPS_bandera
        ,ImporteGPS = $seguros->GPS
        ,TotalExtras = $seguros->total_extras
        ,ImporteDescuento = $importe_descuento
        ,ImporteDescuentoPpgo = $importe_descuento_prepago
        ,MontoAirportFee = $seguros->monto_AF
        ,MontoHotelFee = $seguros->monto_HF
        ,CargoLCRFee = $seguros->LCRF
        ,Subtotal = $subtotal
        ,BanderaIVA = 1
        ,PorIva = $costo->iva
        ,MontoIva = $monto_iva
        ,Total = $total
        ,Telefono ='$telefono'
        ,Email = '$email'
        ,Con_Lic = '$licencia'
        ,Con_Lic_Expira = '$licencia_expira'
        ,Con_Lic_Edo = '$licencia_estado'
        ,Aerolinea = '$aerolinea'
        ,Vuelo = '$vuelo'
        WHERE IDReservacion = '$codigo'");

      if($update != 1)
      {
        return Response::json("Error al actulizar el registro de cotizacion",500);
      }
      $auto->Nombre = $nombre;
      $auto->ApellidoPaterno = $paterno;
      $auto->ApellidoMaterno = $materno;
      $auto->NombreCompleto = $nombre.' '.$paterno.' '.$materno;
      $auto->Telefono = $telefono;
      $auto->Email = $email;
      $auto->Con_Lic = $licencia;
      $auto->Con_Lic_Expira = $licencia_expira;
      $auto->Con_Lic_Edo = $licencia_estado;
      $auto->Aerolinea = $aerolinea;
      $auto->Vuelo = $vuelo;
      return Response::json($auto,201);
    }
  }
