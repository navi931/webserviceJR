<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \stdClass;
use \DateTime;
use Illuminate\Support\Facades\DB;
use \App\object_sorter;
use \App\Calculos;
use \App\Conversiones;
use \App\Validaciones;
use Response;

class Page2Controller extends Controller
{
  //Optimizado
  function getPrecios(Request $request)
  {
    //Dejamos que dure hasta 5 min
    ini_set('max_execution_time', 300);
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
    if(!(isset($request['fecha_inicial']) && isset($request['fecha_final']) && isset($request['id']) && isset($request['id_retorno'])))
    {
      return Response::json('Parametros no mandados correctamente',400);
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

    if(isset($request['SIPPCode']))
    {
      $sipp_code = $request['SIPPCode'];
    }
    else
    {
      $sipp_code = '0';
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

    $id_oficina = $request['id'];
    $id_oficina_retorno = $request['id_retorno'];

    //Encontramos la oficina con el id
    $oficina = DB::select("SELECT IDPlaza,IDTarifa,PorIVA FROM dbo.[WebOficina] WHERE IDOficina = '$id_oficina'");

    if($oficina == null)
    {
      return Response::json('Oficina no encontrada', 500);
    }
    //Cachamos los datos de la oficina
    $oficina = $oficina[0];
    $iva = 1 + ($oficina->PorIVA/100);
    $id_plaza = $oficina->IDPlaza;

    //Obtenemos los datos de la configuracion
    $configuracion = DB::select("SELECT DiasXMes, DiasXSem, HorasxDia,PorDescuentoPpgo FROM dbo.WebConfiguracionGral");
    if($configuracion != null)
    {
      $configuracion = $configuracion[0];
    }

    //Obtenemos los datos de la oficina de regreso
    $oficina_regreso = DB::select("SELECT IDPlaza FROM dbo.[WebOficina] WHERE IDOficina = '$id_oficina_retorno'");
    if(null == $oficina_regreso)
    {
      return Response::json('Oficina no encontrada', 500);
    }

    //Cachamos la plaza de regreso
    $id_plaza_regreso = $oficina_regreso[0]->IDPlaza;

    $grupo_autos = [];

    //Si nos mandan un SIPPCode especifico primero buscamos sos autos y los preferidos
    if($sipp_code != '0')
    {
      $grupo_autos_priori = DB::select("SELECT b.Descripcion,a.Descripcion as descripciongrupocarros,a.DescripcionEN  as descripciongrupocarrosEN, a.IDSIPPCode1,b.IDTAUpgrade1,b.IDTAIpgrade2, b.Ordenamiento,b.Pasajeros,b.MaletasG,b.MaletasCh,b.Puertas,b.RendimientoLitro,b.Transmision,b.AireAcondicionado,b.OtrasCaracteristicas
                                        FROM dbo.[WebGrupoAutos] a LEFT JOIN dbo.[WebSIPPCode] b ON  a.IDSIPPCode1 = b.IDSIPPCode
                                        WHERE a.IDPlaza = $id_plaza AND a.Activo = 1 AND a.IDSIPPCode1 = '$sipp_code'  ORDER BY b.Ordenamiento");

      //Si encontramos el auto específico buscamos sus recomendaciones
      if($grupo_autos_priori != null)
      {
        //Agregamos el auto buscado
        array_push($grupo_autos,$grupo_autos_priori);

        //Se buscan los recomendados
        $id_prefers = [$grupo_autos_priori[0]->IDTAUpgrade1,$grupo_autos_priori[0]->IDTAIpgrade2];
        $grupo_autos_recomendados = [];
        foreach ($id_prefers as $id_prefer)
        {
          $grupo_auto_recomendado = DB::select("SELECT b.Descripcion,a.Descripcion as descripciongrupocarros,a.DescripcionEN  as descripciongrupocarrosEN,a.IDSIPPCode1, b.Ordenamiento, a.Activo,b.Pasajeros,b.MaletasG,b.MaletasCh,b.Puertas,b.RendimientoLitro,b.Transmision,b.AireAcondicionado,b.OtrasCaracteristicas
                                             FROM dbo.[WebGrupoAutos] a LEFT JOIN dbo.[WebSIPPCode] b ON  a.IDSIPPCode1 = b.IDSIPPCode
                                             WHERE a.IDPlaza = $id_plaza AND a.Activo = 1 AND a.IDSIPPCode1 = '$id_prefer'  ORDER BY b.Ordenamiento");
          //Agregamos los autos recomendados
          if($grupo_auto_recomendado != null)
          {
            array_push($grupo_autos,$grupo_auto_recomendado);
          }
        }
       }
      }
    //Obtenemos los carros activos en la plaza y los agregamos
    $grupo_autos_extra = DB::select("SELECT b.Descripcion,a.Descripcion as descripciongrupocarros,a.DescripcionEN  as descripciongrupocarrosEN,a.IDSIPPCode1, b.Ordenamiento,b.Pasajeros,b.MaletasG,b.MaletasCh,b.Puertas,b.RendimientoLitro,b.Transmision,b.AireAcondicionado,b.OtrasCaracteristicas
                               FROM dbo.[WebGrupoAutos] a LEFT JOIN dbo.[WebSIPPCode] b ON  a.IDSIPPCode1 = b.IDSIPPCode
                               WHERE a.IDPlaza = $id_plaza AND a.Activo = 1 ORDER BY b.Ordenamiento");
    array_push($grupo_autos,$grupo_autos_extra);
    //Buscamos repetidos y los eliminamos
    $grupo_autos = array_unique($grupo_autos,SORT_REGULAR);

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

    //Obtenemos las Tarifas
    $tarifas_filtradas =  Calculos::getTarifas($fecha,$id_promocion,$id_cliente,$id_agencia,$id_comisionista,$id_oficina,$id_plaza,$sub_tarifa);

    $autos = [];
    $sippcodes = [];

    //Iremos por cada auto armando el objeto y calculando su tarifa
    foreach ($grupo_autos as $grupo_auto_order)
    {
      foreach ($grupo_auto_order as $grupo_auto)
      {
        if (in_array($grupo_auto->IDSIPPCode1, $sippcodes))
        {
            continue;
        }
        //Creamos el auto y se agrega sus datos
        $auto = new stdClass();
        $auto->Ordenamiento =$grupo_auto->Ordenamiento;
        $auto->descripcion = $grupo_auto->Descripcion;
        $auto->descripciongrupocarros = $grupo_auto->descripciongrupocarros;
        $auto->descripciongrupocarrosEN = $grupo_auto->descripciongrupocarrosEN;
        $auto->SIPPCode = $grupo_auto->IDSIPPCode1;
        array_push($sippcodes,$grupo_auto->IDSIPPCode1);

        //Si es recomendado (preferido) el auto
        if(isset($grupo_auto->Activo))
        {
          $auto->preferido = 1;
        }
        else
        {
          $auto->preferido = 0;
        }
        // Validar tiempo
        $tiempo  = Conversiones::getDaysHours($request['fecha_inicial'],$request['fecha_final']);
        //Validar Promocion
        $promocion = Validaciones::validarCodigoDescuento($id_promocion,$tiempo->fecha_inicial,$tiempo->fecha_final,$tiempo->dias,$id_plaza,$id_oficina,$fecha,$auto->SIPPCode,$id_agencia,$id_comisionista);

        //Obtenemos el tiempo cada vez que lo mandamos
        $tiempo  = Conversiones::getDaysHours($request['fecha_inicial'],$request['fecha_final']);

        //Obtener el precio del auto
        $auto_precio = Calculos::getPrecio($id_cliente,$id_agencia,$id_comisionista,$id_oficina,$id_plaza,$id_plaza_regreso,$es_fin_semana,$configuracion,$promocion,$iva,$edad,$fecha,$tiempo,$tarifas_filtradas,$auto,0);

        if($auto_precio->costo == "No definido")
        {
          continue;
        }

        //0 = Estandar, 1 = Automática, 2 = Continua (Sin Cambios), 3 = "Estandar 4x4, 4 = Automatica 4x4, default = Sin dato
        switch ($grupo_auto->Transmision)
        {
          case 0:
            $auto->Transmision = "Estandar";
            break;
          case 1:
            $auto->Transmision = "Automática";
            break;
          case 2:
            $auto->Transmision = "Continua (Sin Cambios)";
            break;
          case 3:
            $auto->Transmision = "Estandar 4x4";
            break;
          case 4:
            $auto->Transmision = "Automatica 4x4";
            break;
          default:
            $auto->Transmision = "Sin dato";
            break;
        }
        if(1 == $grupo_auto->AireAcondicionado)
        {
          $auto->AireAcondicionado = "Si";
        }
        else
        {
          $auto->AireAcondicionado = "No";
        }

        //Checar si contiene seguros
        if(0 < $auto_precio->costo->seguros->total_seguros_tti || 0 < $auto_precio->costo->seguros->total_extras_tti)
        {
          $auto->ContieneSeguros = 1;
        }
        else
        {
          $auto->ContieneSeguros = 0;
        }
        //Variables de auto
        $auto->Pasajeros = $grupo_auto->Pasajeros;
        $auto->MaletasG = $grupo_auto->MaletasG;
        $auto->MaletasCh = $grupo_auto->MaletasCh;
        $auto->RendimientoLitro = $grupo_auto->RendimientoLitro;
        if ($grupo_auto->AireAcondicionado = 1)
        {
          $auto->AireAcondicionado = "Si";
        }
        else
        {
          $auto->AireAcondicionado = "No";
        }

        $auto->OtrasCaracteristicas = $grupo_auto->OtrasCaracteristicas;

        if(isset($auto_precio->costo->total))
        {
          //Variables de la cotizacion
          $auto->es_prepago = $auto_precio->costo->es_prepago;
          $auto->total = $auto_precio->costo->total;
          $auto->prepago = $auto_precio->costo->prepago;
          $auto->status = 0;
          $auto->Moneda = $auto_precio->Moneda;
          $auto->total_no_promo = $auto_precio->costo->total_no_promo;
          $auto->error = 0;
        }
        else
        {
          //Mensaje de error
          $auto->error = "No se encontro tarifa";
        }

        //Levantar la bandera de promocion
        if(null != $promocion)
        {
          $auto->promocion = true;
        }
        else
        {
          $auto->promocion = false;
        }
        //Agregamos el auto al arreglo de autos
        array_push($autos,$auto);
      }
    }

    //Validamos autos unicos
    $autos = Validaciones::elementosUnicos($autos);

    //Regresamos los autos
    return Response::json($autos,201);
  }
}
