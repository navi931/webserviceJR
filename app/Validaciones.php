<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use \DateTime;
use \stdClass;
use Illuminate\Support\Facades\DB;

class Validaciones
{
  static function validarCodigoDescuento($codigo_descuento,$fecha_inicial,$fecha_final,$dias,$id_plaza,$id_oficina,$fecha_actual,$sipp_code,$id_agencia = '0',$id_comisionista = '0')
  {
    //Buscar agencias para el codigo de descuento
    $agencias = DB::select("SELECT IDAgencia FROM dbo.WebCodigoDescuentoAgencia WHERE IDCodDescuento = '$codigo_descuento'");

    //Es agencia?
    if(($id_agencia != '0' || null != $agencias) && '0' == $id_comisionista)
    {
      //Validacion de agencias
      if(null != $agencias)
      {
        $agencias_temp = [];
        foreach ($agencias as $agencia)
        {
          //Quitar espacios
          $agencia->IDAgencia = str_replace(' ', '', $agencia->IDAgencia);
          array_push($agencias_temp,$agencia->IDAgencia);
        }
        if(!in_array($id_agencia, $agencias_temp))
        {
          return null;
        }
      }
      else
      {
        return null;
      }
    }

    //Buscar comisionistas para el codigo de descuento
    $comisionistas = DB::select("SELECT IDComisionista FROM dbo.WebCodigoDescuentoComisionista WHERE IDCodDescuento = '$codigo_descuento'");

    //Es comisionista?
    if(($id_comisionista != '0' || null != $comisionistas) && '0' == $id_agencia)
    {
      //Validacion de comisionistas
      if(null != $comisionistas)
      {
        $comisionistas_temp = [];
        foreach ($comisionistas as $comisionista)
        {
          //Quitar espacios
          $comisionista->IDComisionista = str_replace(' ', '', $comisionista->IDComisionista);
          array_push($comisionistas_temp,$comisionista->IDComisionista);
        }
        if(!in_array($id_comisionista, $comisionistas_temp))
        {
          return null;
        }
      }
      else
      {
        return null;
      }
    }

    //Buscar plazas para el codigo de descuento
    $plazas = DB::select("SELECT IDPlaza FROM dbo.WebCodigoDescuentoPlaza WHERE IDCodDescuento = '$codigo_descuento'");

    //Validacion de plazas
    if(null != $plazas)
    {
      $plazas_temp = [];
      foreach ($plazas as $plaza)
      {
        //Quitar espacios
        $plaza->IDPlaza = str_replace(' ', '', $plaza->IDPlaza);
        array_push($plazas_temp,$plaza->IDPlaza);
      }
      if(!in_array($id_plaza, $plazas_temp))
      {
        return null;
      }
    }

    //Buscar oficinas para el codigo de descuento
    $oficinas = DB::select("SELECT IDOficina FROM dbo.WebCodigoDescuentoOficina WHERE IDCodDescuento = '$codigo_descuento'");
    //Validacion de oficinas
    if(null != $oficinas)
    {
      $oficinas_temp = [];
      foreach ($oficinas as $oficina)
      {
        //Quitar espacios
        $oficina->IDOficina = str_replace(' ', '', $oficina->IDOficina);
        array_push($oficinas_temp,$oficina->IDOficina);
      }
      if(!in_array($id_oficina, $oficinas_temp))
      {
        return null;
      }
    }

    //Buscar sippCode para el codigo de descuento
    $sipp_codes = DB::select("SELECT IDSIPPCode FROM dbo.WebCodigoDescuentoSC WHERE IDCodDescuento = '$codigo_descuento'");

    //Validacion de sippcode
    if(null != $sipp_codes)
    {
      $sipp_codes_temp = [];
      foreach ($sipp_codes as $sipp_code_db)
      {
        //Quitar espacios
        $sipp_code_db->IDSIPPCode = str_replace(' ', '', $sipp_code_db->IDSIPPCode);
        array_push($sipp_codes_temp,$sipp_code_db->IDSIPPCode);
      }
      if(!in_array($sipp_code, $sipp_codes_temp))
      {
        return null;
      }
    }
    //Checar dia
    $fecha_inicial = date_create_from_format('m-d-Y',$fecha_inicial);
    $fecha_inicial =  date_format($fecha_inicial, 'Y-m-d');
    $dia =  getdate(strtotime($fecha_inicial))['wday'] + 1;

    $fecha_final = date_create_from_format('m-d-Y',$fecha_final);
    $fecha_final =  date_format($fecha_final, 'Y-m-d');

    $promocion = DB::select("SET DATEFORMAT YMD;
    SELECT * FROM dbo.WebCodigoDescuento WHERE IDCodDescuento = '$codigo_descuento' AND FechaValidezIni <= '$fecha_inicial' AND FechaValidezFin >= '$fecha_final'
    AND MinimoDiasContratados <= $dias AND $dias <= MaximoDiasContratados AND (DiaSemanaValido = $dia OR DiaSemanaValido = 0)");
    if(null != $promocion)
    {
      $promocion = $promocion[0];
    }
    return $promocion;
  }

  static function validarUsuario($user,$password)
  {
      $usuario = DB::select("SELECT NombreContacto FROM WebAgencia WHERE IDAgencia = '$user' AND ClaveAcceso = '$password'");
      if(null == $usuario)
      {
        try {
          $usuario = DB::select("SELECT NombreComisionista FROM WebComisionista WHERE IDComisionista = $user AND ClaveAcceso = '$password'");
        } catch (\Exception $e) {
          $usuario = null;
        }
        if(null == $usuario)
        {
          $usuario = DB::select("SELECT NombreCompleto FROM WebClienteLeal WHERE IDCteLeal = '$user'");
          if(null == $usuario)
          {
            return false;
          }
          else
          {
            return $usuario[0];
          }
        }
        else
        {
          return $usuario[0];
        }
      }
      else
      {
        return $usuario[0];
      }

  }

  static function buscarCodigoDescuento($codigo_descuento)
  {
    $promocion = DB::select("SET DATEFORMAT MDY;
    SELECT * FROM dbo.WebCodigoDescuento WHERE IDCodDescuento = '$codigo_descuento'");
    if(null != $promocion)
    {
      $promocion = $promocion[0];
    }
    return $promocion;
  }
  static function elementosUnicos($array)
  {
      $arraySinDuplicados = [];
      foreach($array as $indice => $elemento)
      {
          if (!in_array($elemento, $arraySinDuplicados))
          {
            array_push($arraySinDuplicados,$elemento);
          }
      }
      return $arraySinDuplicados;
  }
}
