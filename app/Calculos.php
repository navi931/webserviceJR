<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use \stdClass;
use \DateTime;
use \App\object_sorter;
use \App\Conversiones;
use Response;
/*  Codigos de error
*
* 1.- No se encontro precios de seguros del PCS 500
* 2.- No se encontro Plan code seguros 500
* 3.- No se encontro edad
*/
class Calculos
{

  //Optimizado
  static function getPrecio($id_cliente,$id_agencia,$id_comisionista,$id_oficina,$id_plaza,$id_plaza_regreso,$es_fin_semana,$configuracion,$promocion,$iva,$edad,$fecha,$tiempo,$tarifas_filtradas,$auto,$seguros)
  {
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
        $auto_nuevo->costo = Calculos::calcularPrecio($fecha,$tiempo,$es_fin_semana,$tarifa,$configuracion,$id_plaza,$id_plaza_regreso,$promocion,$iva,$id_oficina,$tarifa->IDSIPPCode,$edad,$seguros,$precio_seguros);

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
        $auto_nuevo = new stdClass();
        //Si no, mandamos msg de error
        $auto_nuevo->costo = 'No definido';
      }

      //El contador es para cortar algun loop infinito
      $contador++;
    } while ($error == 1 && $contador < 20);
      return $auto_nuevo;
  }

  static function calcularPrecio($fecha,$tiempo, $es_fin_semana = false, $tarifa,$configuracion,$id_plaza,$id_plaza_retorno,$promocion,$iva,$id_oficina,$id_sipp_code,$edad,$seguros = 0,$precio_seguros)
  {

    $ocupacion = Calculos::getOcupacion($id_plaza,$id_sipp_code,$fecha);

    $porcentaje_ocupacion = $ocupacion->porcentaje_ocupacion;
    $porcentaje_inferior = 0;
    $porcentaje_superior = 0;
    $costo_inferior = 0;
    $costo_superior = 0;
    $flota_total = $ocupacion->flota_total;

    $renta = new stdClass();
    $renta->meses_rentados = 0;
    $renta->semanas_rentadas = 0;
    $renta->dias_extras_rentados = 0;
    $renta->dias_rentados = 0;
    $renta->dias_fin_semana = 0;
    $renta->horas_rentadas = 0;

    $horas_por_dia = $configuracion->HorasxDia;
    $dias_por_semana = $tarifa->DiasXSemana;
    $dias_por_mes = $tarifa->DiasXMes;
    $descuentoPpgo =  1 - ($configuracion->PorDescuentoPpgo / 100);

    $renta->HorasxDia = $configuracion->HorasxDia;
    $renta->DiasXSemana = $tarifa->DiasXSemana;
    $renta->DiasXMes = $tarifa->DiasXMes;

    $semana = 7;
    $mes = 30;
    $dias_max_fin_semana = 4;
    $costo_drop_off = 0;

    $dias_temp = $tiempo->dias;
    $horas_temp = $tiempo->horas;

    $renta->meses_reales = floor($dias_temp/$mes);
    $dias_temp = $dias_temp%$mes;

    $renta->semanas_reales = floor($dias_temp/$semana);
    $dias_temp = $dias_temp%$semana;

    $renta->dias_reales = $dias_temp;
    $renta->horas_reales = $horas_temp;

    if($tiempo->horas >= 1)
    {
      $dias_seguros = $tiempo->dias + 1;
    }
    else
    {
      $dias_seguros = $tiempo->dias;
    }
    $renta->dias_seguros = $dias_seguros;
    $extra = false;

    $horas_a_cotizar = $tiempo->horas;

    if($tiempo->horas >= $horas_por_dia)
    {
      $horas_a_cotizar = $horas_por_dia;
    }

    $renta->horas_rentadas = $tiempo->horas;

    if($tiempo->dias == 0)
    {
      $tiempo->dias = 1;
      $tiempo->horas = 0;
    }

    $dias_TK = $tiempo->dias;
    $renta->dias_TK = $dias_TK;
    if($es_fin_semana && $tiempo->dias < $dias_max_fin_semana)
    {
      $renta->dias_fin_semana = $tiempo->dias;
    }
    else
    {
      if($tiempo->dias >= $dias_por_mes)
      {
        $renta->meses_rentados = floor($tiempo->dias/$dias_por_mes);
        $tiempo->dias = $tiempo->dias%$dias_por_mes;
        $extra = true;
        if($tiempo->dias >= $dias_por_mes && $tiempo->dias <= $mes)
        {
          $renta->meses_rentados += 1;
          $tiempo->dias = 0;
        }
      }

      if($tiempo->dias >= $dias_por_semana)
      {
        $renta->semanas_rentadas = floor($tiempo->dias/$semana);
        $tiempo->dias = $tiempo->dias%$semana;
        $extra = true;
        if($tiempo->dias >= $dias_por_semana && $tiempo->dias <= $semana)
        {
          $renta->semanas_rentadas += 1;
          $tiempo->dias = 0;
        }
      }

      if($extra)
      {
        $renta->dias_extras_rentados = $tiempo->dias;
      }
      else
      {
        $renta->dias_rentados = $tiempo->dias;
      }
    }

    if($tarifa->TipoTarifaAplicar != 'TK')
    {
      $seguros_tti = Calculos::getSeguros($renta,$tarifa,$id_plaza,$dias_seguros,$id_oficina,$seguros,$dias_TK,$edad,$precio_seguros);

      $total_extras = $seguros_tti->total_extras_tti / $seguros_tti->dias;
      $total_seguros = $seguros_tti->total_seguros_tti / $seguros_tti->dias;
      $total_fees_porcentaje = $seguros_tti->total_fees_porcentaje_tti;

      if($seguros_tti->tipoSCF == "D")
      {
        $total_extras = $total_extras + ($seguros_tti->total_fees_base_tti / $seguros_tti->dias);
      }

      $mes_tk = $tarifa->TarifaMesTTI;
      $semana_tk = $tarifa->TarifaSemanaTTI;
      $dias_extra_tk = $tarifa->TarifaDiaExtraTTI;
      $dia_tk = $tarifa->TarifaDiaTTI;
      $fin_semana_tk = $tarifa->TarifaFinSemanaTTI;

      //Desglose de IVA
      $mes_tk = $tarifa->TarifaMesTTI / $iva;
      $semana_tk = $tarifa->TarifaSemanaTTI / $iva;
      $dias_extra_tk = $tarifa->TarifaDiaExtraTTI / $iva;
      $dia_tk = $tarifa->TarifaDiaTTI / $iva;
      $fin_semana_tk = $tarifa->TarifaFinSemanaTTI / $iva;

      //Desglose de extras
      $mes_tk = $mes_tk - ($total_extras * $mes);
      $semana_tk = $semana_tk - ($total_extras * $semana);
      $dias_extra_tk = $dias_extra_tk - ($total_extras * 1);
      $dia_tk = $dia_tk - ($total_extras * 1);
      $fin_semana_tk = $fin_semana_tk - ($total_extras * 1);

      //Desglose de fees
      $mes_tk = $mes_tk / $total_fees_porcentaje;
      $semana_tk = $semana_tk / $total_fees_porcentaje;
      $dias_extra_tk = $dias_extra_tk / $total_fees_porcentaje;
      $dia_tk = $dia_tk / $total_fees_porcentaje;
      $fin_semana_tk = $fin_semana_tk / $total_fees_porcentaje;

      //Desglose de seguros
      $mes_tk = $mes_tk - ($total_seguros * $mes);
      $semana_tk = $semana_tk - ($total_seguros * $semana);
      $dias_extra_tk = $dias_extra_tk - ($total_seguros * 1);
      $dia_tk = $dia_tk - ($total_seguros * 1);
      $fin_semana_tk = $fin_semana_tk - ($total_seguros * 1);

      //Asignacion de Tarifas TK Inferior
      $tarifa->TarifaMesTKInf = $mes_tk;
      $tarifa->TarifaSemanaTKInf = $semana_tk;
      $tarifa->TarifaDiaExtraTKInf = $dias_extra_tk;
      $tarifa->TarifaDiaTKInf = $dia_tk;
      $tarifa->TarifaFinSemanaTKInf = $fin_semana_tk;
      $tarifa->TarifaHoraTKInf = $dia_tk / $horas_por_dia;

      //Asignacion de Tarifas TK Superior
      $tarifa->TarifaMesTKSup = $mes_tk;
      $tarifa->TarifaSemanaTKSup = $semana_tk;
      $tarifa->TarifaDiaExtraTKSup = $dias_extra_tk;
      $tarifa->TarifaDiaTKSup = $dia_tk;
      $tarifa->TarifaFinSemanaTKSup = $fin_semana_tk;
      $tarifa->TarifaHoraTKSup = $dia_tk / $horas_por_dia;

    }
    $tarifa->POInfTMes = $tarifa->POInfTMes / 100;
    $tarifa->POSupTMes = $tarifa->POSupTMes / 100;
    $tarifa->POInfTSemana = $tarifa->POInfTSemana / 100;
    $tarifa->POSupTSemana = $tarifa->POSupTSemana / 100;
    $tarifa->POInfTDiaExtra = $tarifa->POInfTDiaExtra / 100;
    $tarifa->POSupTDiaExtra = $tarifa->POSupTDiaExtra / 100;
    $tarifa->POInfTDia = $tarifa->POInfTDia / 100;
    $tarifa->POSupTDia = $tarifa->POSupTDia / 100;
    $tarifa->POInfTFinSemana = $tarifa->POInfTFinSemana / 100;
    $tarifa->POSupTFinSemana = $tarifa->POSupTFinSemana / 100;

    $renta->precio_por_mes = Calculos::getCosto($porcentaje_ocupacion,$tarifa->POInfTMes,$tarifa->POSupTMes,$tarifa->TarifaMesTKInf,$tarifa->TarifaMesTKSup,$flota_total);
    $renta->precio_por_semana = Calculos::getCosto($porcentaje_ocupacion,$tarifa->POInfTSemana,$tarifa->POSupTSemana,$tarifa->TarifaSemanaTKInf,$tarifa->TarifaSemanaTKSup,$flota_total);
    $renta->precio_por_dia_extra = Calculos::getCosto($porcentaje_ocupacion,$tarifa->POInfTDiaExtra,$tarifa->POSupTDiaExtra,$tarifa->TarifaDiaExtraTKInf,$tarifa->TarifaDiaExtraTKSup,$flota_total);
    $renta->precio_por_dia = Calculos::getCosto($porcentaje_ocupacion,$tarifa->POInfTDia,$tarifa->POSupTDia,$tarifa->TarifaDiaTKInf,$tarifa->TarifaDiaTKSup,$flota_total);
    $renta->precio_por_dia_fin_semana = Calculos::getCosto($porcentaje_ocupacion,$tarifa->POInfTFinSemana,$tarifa->POSupTFinSemana,$tarifa->TarifaFinSemanaTKInf,$tarifa->TarifaFinSemanaTKSup,$flota_total);
    $renta->precio_por_hora = $renta->precio_por_dia / $horas_por_dia;

    $renta->costo_mes = $renta->precio_por_mes * $renta->meses_rentados;
    $renta->costo_semana = $renta->precio_por_semana* $renta->semanas_rentadas;
    $renta->costo_dia_extra = $renta->precio_por_dia_extra* $renta->dias_extras_rentados;
    $renta->costo_dia = $renta->precio_por_dia* $renta->dias_rentados;
    $renta->costo_dia_fin_semana = $renta->precio_por_dia_fin_semana* $renta->dias_fin_semana;
    $renta->costo_hora = $renta->precio_por_hora * $horas_a_cotizar;

    $renta->seguros = Calculos::getSeguros($renta,$tarifa,$id_plaza,$dias_seguros,$id_oficina,$seguros,$dias_TK,$edad,$precio_seguros);

    $dia_reserva = date_create_from_format('m-d-Y',$fecha);

    $now = new DateTime('now');
    $dia_reserva = new DateTime(date_format($dia_reserva, 'Y-m-d'));

    $diff = $now->diff($dia_reserva);
    $diferencia_dia_reservacion = $diff->days;

    $renta->descuento_reserva_anticipada = 1;
    if($tarifa->FTDescPor > 0 && $diferencia_dia_reservacion >= $tarifa->FTDescPorDiasAntRes)
    {
      $renta->descuento_reserva_anticipada = 1 - ($tarifa->FTDescPor/100);
    }

    if($tarifa->FTIncPor > 0 && $diferencia_dia_reservacion <= $tarifa->FTIncPorDiasAntRes && $porcentaje_ocupacion >= $tarifa->FTIncPorAplicarPO/100)
    {
      $renta->descuento_reserva_anticipada = 1 + ($tarifa->FTIncPor/100);
    }

    if($renta->seguros->status)
    {
      return $renta;
    }

    $renta->total_TK = ($renta->costo_mes + $renta->costo_semana + $renta->costo_dia_extra + $renta->costo_dia + $renta->costo_dia_fin_semana + $renta->costo_hora);
    $renta->total_TK_no_promo = ($renta->costo_mes + $renta->costo_semana + $renta->costo_dia_extra + $renta->costo_dia + $renta->costo_dia_fin_semana + $renta->costo_hora);

    //Ya teniendo el tk captamos el monto de los fees
    $renta->seguros->LCRF = round((($renta->seguros->LCRF/100) * ($renta->total_TK + $renta->seguros->total_seguros)),2);
    // $renta->seguros->AF = round((($renta->seguros->AF/100) * $renta->total_TK),2);
    // $renta->seguros->HF = round((($renta->seguros->HF/100) * $renta->total_TK),2);

    $renta->seguros->LCRF_tti = round((($renta->seguros->LCRF_tti/100) * ($renta->total_TK + $renta->seguros->total_seguros_tti)),2);
    // $renta->seguros->AF_tti = round((($renta->seguros->AF_tti/100) * $renta->total_TK),2);
    // $renta->seguros->HF_tti = round((($renta->seguros->HF_tti/100) * $renta->total_TK),2);

    $renta->total_seguros = $renta->seguros->total_seguros;
    $renta->porcentaje_fees = $renta->seguros->total_fees_porcentaje;
    $renta->fees_base = $renta->seguros->total_fees_base;
    $renta->total_extras = $renta->seguros->total_extras;
    $renta->iva = $iva;

    $renta->descuento_promo_global =  1;
    $renta->descuento_promo_TK =  1;
    $renta->descuento_promo_montoMN =  0;
    $renta->descuento_promo_dias =  0;

    $renta->P_descuento_promo_global =  1;
    $renta->P_descuento_promo_TK =  1;
    $renta->P_descuento_promo_montoMN = 0;
    $renta->P_descuento_promo_dias = 0;

    $monto = 0;
    $P_monto = 0;
    $renta->Moneda = $tarifa->Moneda;

    if(null != $promocion)
    {
      $renta->descuento_promo_global =  1 - ($promocion->PorcentajeGlobal/100);
      $renta->descuento_promo_TK =  1 - ($promocion->PorcentajeTK/100);
      $renta->descuento_promo_montoMN =  $promocion->MontoMN;
      $renta->descuento_promo_montoUSD = $promocion->MontoUSD;
      $renta->descuento_promo_dias =  $promocion->Dias;

      $renta->P_descuento_promo_global =  1 - ($promocion->P_PorcentajeGlobal/100);
      $renta->P_descuento_promo_TK =  1 - ($promocion->P_PorcentajeTK/100);
      $renta->P_descuento_promo_montoMN = $promocion->P_MontoMN;
      $renta->P_descuento_promo_montoUSD = $promocion->P_MontoUSD;
      $renta->P_descuento_promo_dias = $promocion->P_Dias;

      $renta->Moneda = $tarifa->Moneda;

      if($renta->Moneda == 'USD')
      {
        $monto = $renta->descuento_promo_montoUSD;
        $P_monto = $renta->P_descuento_promo_montoUSD;
      }
      else
      {
        $monto = $renta->descuento_promo_montoMN;
        $P_monto = $renta->P_descuento_promo_montoMN;
      }
    }


    if($id_plaza != $id_plaza_retorno)
    {
      $registro_costo_drop_off = DB::select("SELECT ImporteDropOffMN,ImporteDropOffUSD FROM dbo.[WebDropOff] WHERE IDPlazaRenta = '$id_plaza' AND IDPlazaRetorna = '$id_plaza_retorno' AND IDSIPPCode = '$id_sipp_code' AND CobraDropOff = '1'");
      if($registro_costo_drop_off != null)
      {
        $registro_costo_drop_off = $registro_costo_drop_off[0];
        if($renta->Moneda == 'USD')
        {
          $costo_drop_off = $registro_costo_drop_off->ImporteDropOffUSD;
        }
        else
        {
          $costo_drop_off = $registro_costo_drop_off->ImporteDropOffMN;
        }
      }
      else
      {
        $registro_costo_drop_off = DB::select("SELECT ImporteDropOffUSD FROM dbo.[WebDropOff] WHERE IDPlazaRenta = '$id_plaza' AND IDPlazaRetorna = '$id_plaza_retorno'AND CobraDropOff = '1'");
        if($registro_costo_drop_off != null)
        {
          $registro_costo_drop_off = $registro_costo_drop_off[0];
          if($renta->Moneda == 'USD')
          {
            $costo_drop_off = $registro_costo_drop_off->ImporteDropOffUSD;
          }
          else
          {
            $costo_drop_off = $registro_costo_drop_off->ImporteDropOffMN;
          }
        }
      }
    }


    $renta->seguros->monto_AF = ($renta->total_TK + $renta->total_seguros) * ($renta->seguros->AF/100);
    $renta->seguros->monto_HF = ($renta->total_TK + $renta->total_seguros) * ($renta->seguros->HF/100);

    $renta->drop_off = $costo_drop_off;

    $descuento_al_TK = 1 - (1 - $renta->descuento_reserva_anticipada) - ( 1 - $renta->descuento_promo_TK);
    $descuento_al_TK_prepago = 1 - (1 - $renta->descuento_reserva_anticipada) - ( 1 - $renta->P_descuento_promo_TK);

    if($renta->P_descuento_promo_global < 1)
    {
      $descuentoPpgo = $renta->P_descuento_promo_global;
    }

    $renta->subtotal_no_promo = ((($renta->total_TK_no_promo + $renta->total_seguros)* $renta->porcentaje_fees) + $renta->total_extras + $renta->fees_base) + $renta->drop_off;

    $renta->subtotal = ((((($renta->total_TK*$descuento_al_TK + $renta->total_seguros)* $renta->porcentaje_fees) + $renta->total_extras + $renta->fees_base) + $renta->drop_off) * $renta->descuento_promo_global) - $monto - ($renta->descuento_promo_dias * $renta->precio_por_dia);
    $renta->subtotal_prepago = ((((($renta->total_TK*$descuento_al_TK_prepago + $renta->total_seguros) * $renta->porcentaje_fees) + $renta->total_extras + $renta->fees_base)  + $renta->drop_off) * $descuentoPpgo)
    - $P_monto - ($renta->P_descuento_promo_dias * $renta->precio_por_dia);

    if($renta->subtotal < 0)
    {
      $renta->subtotal = 0;
    }
    if($renta->subtotal_prepago < 0)
    {
      $renta->subtotal_prepago = 0;
    }
    if($renta->subtotal_no_promo < 0)
    {
      $renta->subtotal_no_promo = 0;
    }

    $renta->total_no_promo = $renta->subtotal_no_promo * $iva;
    $renta->total = $renta->subtotal * $iva;
    $renta->prepago = $renta->subtotal_prepago * $iva;

    $renta->total_no_promo = Calculos::redondear_dos_decimal($renta->total_no_promo);
    $renta->total = Calculos::redondear_dos_decimal($renta->total);
    $renta->prepago = Calculos::redondear_dos_decimal($renta->prepago);

    $renta->monto_iva_total = Calculos::redondear_dos_decimal($renta->total - $renta->subtotal);
    $renta->monto_iva_prepago = Calculos::redondear_dos_decimal($renta->prepago - $renta->subtotal_prepago);

    $renta->promocion_prepago = Calculos::redondear_dos_decimal($renta->subtotal_no_promo - $renta->subtotal_prepago);
    $renta->promocion_codigo = Calculos::redondear_dos_decimal($renta->subtotal_no_promo - $renta->subtotal);
    return $renta;
  }

  static function getSeguros($renta,$tarifa,$id_plaza,$dias,$id_oficina,$seguros,$dias_TK,$edad,$precio_seguros)
  {
    $oficina = DB::select("SELECT PorAF,PorHF,LCRF,SCFMN,TipoCobroSCF,SCF FROM dbo.[WebOficina] WHERE IDOficina = '$id_oficina'")[0];
    $seguros_edad = DB::select("SELECT BanderaCM,BanderaCDW,BanderaDP,BanderaPAI,BanderaPLI,BanderaPLIA FROM dbo.WebEdad WHERE edad = '$edad'");
    $venta_seguros_plaza = DB::select("SELECT VentaWEBCDW,VentaWEBDP,VentaWEBPAI,VentaWEBPLI,VentaWEBPLIA,VentaWEBMDW FROM dbo.WebPlaza WHERE IDPlaza = '$id_plaza'")[0];


    if(null != $seguros_edad)
    {
      $seguros_edad = $seguros_edad[0];
    }
    else
    {
      return 3;
    }
    $seguro = new stdClass();
    $seguro->status = 0;

    $plan_code_seguros = DB::select("SELECT * FROM dbo.WebPlanCode WHERE IDPCode = '$tarifa->IDPCode'");
    if($plan_code_seguros == null)
    {
      $plan_code_seguros->IDPCode = 0;
      $plan_code_seguros->Descripcion = 0;
      $plan_code_seguros->Prepago = 0;
      $plan_code_seguros->DP = 0;
      $plan_code_seguros->CDW = 0;
      $plan_code_seguros->PAI = 0;
      $plan_code_seguros->PLI = 0;
      $plan_code_seguros->PLIA = 0;
      $plan_code_seguros->MDW = 0;
      $plan_code_seguros->ERA = 0;
      $plan_code_seguros->ETS = 0;
      $plan_code_seguros->CA = 0;
      $plan_code_seguros->BS1 = 0;
      $plan_code_seguros->BS2 = 0;
      $plan_code_seguros->BS3 = 0;
      $plan_code_seguros->CM = 0;
      $plan_code_seguros->GPS = 0;
      $plan_code_seguros->DropOff = 0;
      $plan_code_seguros->PickUp = 0;
      $plan_code_seguros->LCRF = 0;
      $plan_code_seguros->SCF = 0;
      $plan_code_seguros->IVA = 0;
      $plan_code_seguros->AF = 0;
      $plan_code_seguros->HF = 0;
    }
    else
    {
      $plan_code_seguros = $plan_code_seguros[0];
    }

    if('USD'== $tarifa->Moneda)
    {
      $precio_seguros->DPMN = $precio_seguros->DPUSD;
      $precio_seguros->CDWMN = $precio_seguros->CDWUSD;
      $precio_seguros->PAIMN = $precio_seguros->PAIUSD;
      $precio_seguros->PLIMN = $precio_seguros->PLIUSD;
      $precio_seguros->PLIAMN = $precio_seguros->PLIAUSD;
      $precio_seguros->MDWMN = $precio_seguros->MDWUSD;

      $precio_seguros->ERAMN = $precio_seguros->ERAUSD;
      $precio_seguros->ETSMN = $precio_seguros->ETSUSD;
      $precio_seguros->CAMN = $precio_seguros->CAUSD;
      $precio_seguros->BS1MN = $precio_seguros->BS1USD;
      $precio_seguros->BS2MN = $precio_seguros->BS2USD;
      $precio_seguros->BS3MN = $precio_seguros->BS3USD;
      $precio_seguros->CMMN = $precio_seguros->CMUSD;
      $precio_seguros->GPSMN = $precio_seguros->GPSUSD;

      $oficina->SCFMN = $oficina->SCF;
    }

    if($seguros != 0)
    {
      $seguros['DP'] = min($seguros['DP'], 1);
      $seguros['CDW'] = min($seguros['CDW'], 1);
      $seguros['PAI'] = min($seguros['PAI'], 1);
      $seguros['PLI'] = min($seguros['PLI'], 1);
      $seguros['PLIA'] = min($seguros['PLIA'], 1);
      $seguros['MDW'] = min($seguros['MDW'], 1);
      $seguros['ERA'] = min($seguros['ERA'], 1);
      $seguros['ETS'] = min($seguros['ETS'], 1);
      $seguros['Prepago'] = min($seguros['Prepago'], 1);
      //Estos seguros si se pueden agregar a mas de uno
      // $seguros['CA'] = min($seguros['CA'], 1);
      // $seguros['BS1'] = min($seguros['BS1'], 1);
      // $seguros['BS2'] = min($seguros['BS2'], 1);
      // $seguros['BS3'] = min($seguros['BS3'], 1);
      // $seguros['CM'] = min($seguros['CM'], 1);
      // $seguros['GPS'] = min($seguros['GPS'], 1);
    }
    else
    {
      $seguros = ['DP'=> 0,'CDW'=> 0,'PAI'=> 0,'PLI'=> 0,'PLIA'=> 0,'Prepago'=>0,
      'MDW'=> 0,'ERA'=> 0,'ETS'=> 0,'CA'=> 0,'BS1'=> 0,'BS2'=> 0,'BS3'=> 0,
      'CM'=> 0,'GPS'=> 0,'LCRF'=>1,'AF'=>1,'HF'=>1,'SCF'=>1];
    }

    $renta->es_prepago = $seguros['Prepago'];
    $seguro->dias = $dias;

    $seguro->ventaDP = intval($venta_seguros_plaza->VentaWEBDP);
    $seguro->ventaCDW = intval($venta_seguros_plaza->VentaWEBCDW);
    $seguro->ventaPAI = intval($venta_seguros_plaza->VentaWEBPAI);
    $seguro->ventaPLI = intval($venta_seguros_plaza->VentaWEBPLI);
    $seguro->ventaPLIA = intval($venta_seguros_plaza->VentaWEBPLIA);
    $seguro->ventaMDW = intval($venta_seguros_plaza->VentaWEBMDW);

    $seguro->DP_bandera = Conversiones::theBigger($seguros['DP'],($plan_code_seguros->DP | $seguros_edad->BanderaDP)) & $venta_seguros_plaza->VentaWEBDP;
    $seguro->CDW_bandera = Conversiones::theBigger($seguros['CDW'],($plan_code_seguros->CDW | $seguros_edad->BanderaCDW)) & $venta_seguros_plaza->VentaWEBCDW;
    $seguro->PAI_bandera = Conversiones::theBigger($seguros['PAI'],($plan_code_seguros->PAI | $seguros_edad->BanderaPAI)) & $venta_seguros_plaza->VentaWEBPAI;
    $seguro->PLI_bandera = Conversiones::theBigger($seguros['PLI'],($plan_code_seguros->PLI | $seguros_edad->BanderaPLI)) & $venta_seguros_plaza->VentaWEBPLI;
    $seguro->PLIA_bandera = Conversiones::theBigger($seguros['PLIA'],($plan_code_seguros->PLIA | $seguros_edad->BanderaPLIA)) & $venta_seguros_plaza->VentaWEBPLIA;
    $seguro->MDW_bandera = Conversiones::theBigger($seguros['MDW'],$plan_code_seguros->MDW) & $venta_seguros_plaza->VentaWEBPLIA;

    $seguro->ERA_bandera = Conversiones::theBigger($seguros['ERA'],$plan_code_seguros->ERA);
    $seguro->ETS_bandera = Conversiones::theBigger($seguros['ETS'],$plan_code_seguros->ETS);
    $seguro->CA_bandera = Conversiones::theBigger($seguros['CA'],$plan_code_seguros->CA);
    $seguro->BS1_bandera = Conversiones::theBigger($seguros['BS1'],$plan_code_seguros->BS1);
    $seguro->BS2_bandera = Conversiones::theBigger($seguros['BS2'],$plan_code_seguros->BS2);
    $seguro->BS3_bandera = Conversiones::theBigger($seguros['BS3'],$plan_code_seguros->BS3);
    $seguro->CM_bandera = Conversiones::theBigger($seguros['CM'],($plan_code_seguros->CM | $seguros_edad->BanderaCM));
    $seguro->GPS_bandera = Conversiones::theBigger($seguros['GPS'],$plan_code_seguros->GPS);

    //SEGUROS CONDICIONALES
    //DP
    if(1 == $seguro->DP_bandera)
    {
      $seguros_condicionales = DB::select("SELECT * FROM dbo.[WebCondicionesCoberturas] WHERE IDPlaza = '$id_plaza' and IDSeguro = 'DP'");
      if(null == $seguros_condicionales)
      {
        $seguros_condicionales = DB::select("SELECT * FROM dbo.[WebCondicionesCoberturas] WHERE IDPlaza = '' and IDSeguro = 'DP'");
      }
      if(null != $seguros_condicionales)
      {
        $seguro->DP_bandera = max($seguro->DP_bandera,$seguros_condicionales[0]->DP);
        $seguro->CDW_bandera = max($seguro->CDW_bandera,$seguros_condicionales[0]->CDW);
        $seguro->PAI_bandera = max($seguro->PAI_bandera,$seguros_condicionales[0]->PAI);
        $seguro->PLI_bandera = max($seguro->PLI_bandera,$seguros_condicionales[0]->PLI);
        $seguro->PLIA_bandera = max($seguro->PLIA_bandera,$seguros_condicionales[0]->PLIA);
        $seguro->MDW_bandera = max($seguro->MDW_bandera,$seguros_condicionales[0]->MDW);
      }
    }
    //CDW
    if(1 == $seguro->CDW_bandera)
    {
      $seguros_condicionales = DB::select("SELECT * FROM dbo.[WebCondicionesCoberturas] WHERE IDPlaza = '$id_plaza' and IDSeguro = 'CDW'");
      if(null == $seguros_condicionales)
      {
        $seguros_condicionales = DB::select("SELECT * FROM dbo.[WebCondicionesCoberturas] WHERE IDPlaza = '' and IDSeguro = 'CDW'");
      }
      if(null != $seguros_condicionales)
      {
        $seguro->DP_bandera = max($seguro->DP_bandera,$seguros_condicionales[0]->DP);
        $seguro->CDW_bandera = max($seguro->CDW_bandera,$seguros_condicionales[0]->CDW);
        $seguro->PAI_bandera = max($seguro->PAI_bandera,$seguros_condicionales[0]->PAI);
        $seguro->PLI_bandera = max($seguro->PLI_bandera,$seguros_condicionales[0]->PLI);
        $seguro->PLIA_bandera = max($seguro->PLIA_bandera,$seguros_condicionales[0]->PLIA);
        $seguro->MDW_bandera = max($seguro->MDW_bandera,$seguros_condicionales[0]->MDW);
      }
    }
    //PAI
    if(1 == $seguro->PAI_bandera)
    {
      $seguros_condicionales = DB::select("SELECT * FROM dbo.[WebCondicionesCoberturas] WHERE IDPlaza = '$id_plaza' and IDSeguro = 'PAI'");
      if(null == $seguros_condicionales)
      {
        $seguros_condicionales = DB::select("SELECT * FROM dbo.[WebCondicionesCoberturas] WHERE IDPlaza = '' and IDSeguro = 'PAI'");
      }
      if(null != $seguros_condicionales)
      {
        $seguro->DP_bandera = max($seguro->DP_bandera,$seguros_condicionales[0]->DP);
        $seguro->CDW_bandera = max($seguro->CDW_bandera,$seguros_condicionales[0]->CDW);
        $seguro->PAI_bandera = max($seguro->PAI_bandera,$seguros_condicionales[0]->PAI);
        $seguro->PLI_bandera = max($seguro->PLI_bandera,$seguros_condicionales[0]->PLI);
        $seguro->PLIA_bandera = max($seguro->PLIA_bandera,$seguros_condicionales[0]->PLIA);
        $seguro->MDW_bandera = max($seguro->MDW_bandera,$seguros_condicionales[0]->MDW);
      }
    }
    //PLI
    if(1 == $seguro->PLI_bandera)
    {
      $seguros_condicionales = DB::select("SELECT * FROM dbo.[WebCondicionesCoberturas] WHERE IDPlaza = '$id_plaza' and IDSeguro = 'PLI'");
      if(null == $seguros_condicionales)
      {
        $seguros_condicionales = DB::select("SELECT * FROM dbo.[WebCondicionesCoberturas] WHERE IDPlaza = '' and IDSeguro = 'PLI'");
      }
      if(null != $seguros_condicionales)
      {
        $seguro->DP_bandera = max($seguro->DP_bandera,$seguros_condicionales[0]->DP);
        $seguro->CDW_bandera = max($seguro->CDW_bandera,$seguros_condicionales[0]->CDW);
        $seguro->PAI_bandera = max($seguro->PAI_bandera,$seguros_condicionales[0]->PAI);
        $seguro->PLI_bandera = max($seguro->PLI_bandera,$seguros_condicionales[0]->PLI);
        $seguro->PLIA_bandera = max($seguro->PLIA_bandera,$seguros_condicionales[0]->PLIA);
        $seguro->MDW_bandera = max($seguro->MDW_bandera,$seguros_condicionales[0]->MDW);
      }
    }
    //PLIA
    if(1 == $seguro->PLIA_bandera)
    {
      $seguros_condicionales = DB::select("SELECT * FROM dbo.[WebCondicionesCoberturas] WHERE IDPlaza = '$id_plaza' and IDSeguro = 'PLIA'");
      if(null == $seguros_condicionales)
      {
        $seguros_condicionales = DB::select("SELECT * FROM dbo.[WebCondicionesCoberturas] WHERE IDPlaza = '' and IDSeguro = 'PLIA'");
      }
      if(null != $seguros_condicionales)
      {
        $seguro->DP_bandera = max($seguro->DP_bandera,$seguros_condicionales[0]->DP);
        $seguro->CDW_bandera = max($seguro->CDW_bandera,$seguros_condicionales[0]->CDW);
        $seguro->PAI_bandera = max($seguro->PAI_bandera,$seguros_condicionales[0]->PAI);
        $seguro->PLI_bandera = max($seguro->PLI_bandera,$seguros_condicionales[0]->PLI);
        $seguro->PLIA_bandera = max($seguro->PLIA_bandera,$seguros_condicionales[0]->PLIA);
        $seguro->MDW_bandera = max($seguro->MDW_bandera,$seguros_condicionales[0]->MDW);
      }
    }
    //MDW
    if(1 == $seguro->MDW_bandera)
    {
      $seguros_condicionales = DB::select("SELECT * FROM dbo.[WebCondicionesCoberturas] WHERE IDPlaza = '$id_plaza' and IDSeguro = 'MDW'");
      if(null == $seguros_condicionales)
      {
        $seguros_condicionales = DB::select("SELECT * FROM dbo.[WebCondicionesCoberturas] WHERE IDPlaza = '' and IDSeguro = 'MDW'");
      }
      if(null != $seguros_condicionales)
      {
        $seguro->DP_bandera = max($seguro->DP_bandera,$seguros_condicionales[0]->DP);
        $seguro->CDW_bandera = max($seguro->CDW_bandera,$seguros_condicionales[0]->CDW);
        $seguro->PAI_bandera = max($seguro->PAI_bandera,$seguros_condicionales[0]->PAI);
        $seguro->PLI_bandera = max($seguro->PLI_bandera,$seguros_condicionales[0]->PLI);
        $seguro->PLIA_bandera = max($seguro->PLIA_bandera,$seguros_condicionales[0]->PLIA);
        $seguro->MDW_bandera = max($seguro->MDW_bandera,$seguros_condicionales[0]->MDW);
      }
    }

    //Costo de los seguros, se cobren o no
    $seguro->DP_costo = $dias * $precio_seguros->DPMN;
    $seguro->CDW_costo = $dias * $precio_seguros->CDWMN;
    $seguro->PAI_costo = $dias * $precio_seguros->PAIMN;
    $seguro->PLI_costo = $dias * $precio_seguros->PLIMN;
    $seguro->PLIA_costo = $dias * $precio_seguros->PLIAMN;
    $seguro->MDW_costo = $dias * $precio_seguros->MDWMN;

    $seguro->ERA_costo = $dias * $precio_seguros->ERAMN;
    $seguro->ETS_costo = $dias * $precio_seguros->ETSMN;
    $seguro->CA_costo = $dias * $precio_seguros->CAMN;
    $seguro->BS1_costo = $dias * $precio_seguros->BS1MN;
    $seguro->BS2_costo = $dias * $precio_seguros->BS2MN;
    $seguro->BS3_costo = $dias * $precio_seguros->BS3MN;
    $seguro->CM_costo = $dias * $precio_seguros->CMMN;
    $seguro->GPS_costo = $dias * $precio_seguros->GPSMN;

    //Calculamos los seguros de la tarifa
    $seguro->DP = $seguro->DP_costo * $seguro->DP_bandera;
    $seguro->CDW = $seguro->CDW_costo * $seguro->CDW_bandera;
    $seguro->PAI = $seguro->PAI_costo * $seguro->PAI_bandera;
    $seguro->PLI = $seguro->PLI_costo * $seguro->PLI_bandera;
    $seguro->PLIA = $seguro->PLIA_costo * $seguro->PLIA_bandera;
    $seguro->MDW = $seguro->MDW_costo * $seguro->MDW_bandera;

    $seguro->ERA = $seguro->ERA_costo * $seguro->ERA_bandera;
    $seguro->ETS = $seguro->ETS_costo * $seguro->ETS_bandera;
    $seguro->CA = $seguro->CA_costo * $seguro->CA_bandera;
    $seguro->BS1 = $seguro->BS1_costo * $seguro->BS1_bandera;
    $seguro->BS2 = $seguro->BS2_costo * $seguro->BS2_bandera;
    $seguro->BS3 = $seguro->BS3_costo * $seguro->BS3_bandera;
    $seguro->CM = $seguro->CM_costo * $seguro->CM_bandera;
    $seguro->GPS = $seguro->GPS_costo * $seguro->GPS_bandera;

    $seguro->LCRF = 1 * $oficina->LCRF;
    $seguro->AF = 1 * $oficina->PorAF;
    $seguro->HF = 1 * $oficina->PorHF;

    if($oficina->TipoCobroSCF == 'D')
    {
      $seguro->SCF = 1 * $oficina->SCFMN * $renta->dias_seguros;
      $seguro->tipoSCF = 'D';
    }
    else
    {
      $seguro->SCF = 1 * $oficina->SCFMN;
      $seguro->tipoSCF = 'U';
    }

    $seguro->total_seguros = $seguro->DP + $seguro->CDW + $seguro->PAI + $seguro->PLI + $seguro->PLIA + $seguro->MDW;
    $seguro->total_extras = $seguro->ERA +$seguro->ETS + $seguro->CA + $seguro->BS1 + $seguro->BS2 + $seguro->BS3 + $seguro->CM + $seguro->GPS;
    $seguro->total_fees_porcentaje = 1 + (($seguro->LCRF + $seguro->AF + $seguro->HF)/100);
    $seguro->total_fees_base = $seguro->SCF;

    $seguro->LCRF_bandera = min(1 * $oficina->LCRF,1);
    $seguro->AF_bandera = min(1 * $oficina->PorAF,1);
    $seguro->HF_bandera = min(1 * $oficina->PorHF,1);

    $seguro->SCF_bandera = min(1 * $oficina->SCFMN,1);

    $seguro->tipoSCF = $oficina->TipoCobroSCF;

    //tti
    $seguro->DP_tti = $dias * $precio_seguros->DPMN * $plan_code_seguros->DP;
    $seguro->CDW_tti = $dias * $precio_seguros->CDWMN * $plan_code_seguros->CDW;
    $seguro->PAI_tti = $dias * $precio_seguros->PAIMN * $plan_code_seguros->PAI;
    $seguro->PLI_tti = $dias * $precio_seguros->PLIMN * $plan_code_seguros->PLI;
    $seguro->PLIA_tti = $dias * $precio_seguros->PLIAMN * $plan_code_seguros->PLIA;
    $seguro->MDW_tti = $dias * $precio_seguros->MDWMN * $plan_code_seguros->MDW;

    $seguro->ERA_tti = $dias * $precio_seguros->ERAMN * $plan_code_seguros->ERA;
    $seguro->ETS_tti = $dias * $precio_seguros->ETSMN * $plan_code_seguros->ETS;
    $seguro->CA_tti = $dias * $precio_seguros->CAMN * $plan_code_seguros->CA;
    $seguro->BS1_tti = $dias * $precio_seguros->BS1MN * $plan_code_seguros->BS1;
    $seguro->BS2_tti = $dias * $precio_seguros->BS2MN * $plan_code_seguros->BS2;
    $seguro->BS3_tti = $dias * $precio_seguros->BS3MN * $plan_code_seguros->BS3;
    $seguro->CM_tti = $dias * $precio_seguros->CMMN * $plan_code_seguros->CM;
    $seguro->GPS_tti = $dias * $precio_seguros->GPSMN * $plan_code_seguros->GPS;

    $seguro->LCRF_tti = $plan_code_seguros->LCRF * $oficina->LCRF;
    $seguro->AF_tti = $plan_code_seguros->AF * $oficina->PorAF;
    $seguro->HF_tti = $plan_code_seguros->HF * $oficina->PorHF;

    if($oficina->TipoCobroSCF == 'D')
    {
      $seguro->SCF_tti = $plan_code_seguros->SCF * $oficina->SCFMN * $dias_TK;
      $seguro->tipoSCF = 'D';
    }
    else
    {
      $seguro->SCF_tti = $plan_code_seguros->SCF * $oficina->SCFMN;
      $seguro->tipoSCF = 'U';
    }

    $seguro->total_seguros_tti = $seguro->DP_tti + $seguro->CDW_tti + $seguro->PAI_tti + $seguro->PLI_tti + $seguro->PLIA_tti + $seguro->MDW_tti;
    $seguro->total_extras_tti = $seguro->ERA_tti +$seguro->ETS_tti + $seguro->CA_tti + $seguro->BS1_tti + $seguro->BS2_tti + $seguro->BS3_tti + $seguro->CM_tti + $seguro->GPS_tti;
    $seguro->total_fees_porcentaje_tti = 1 + (($seguro->LCRF_tti + $seguro->AF_tti + $seguro->HF_tti)/100);
    $seguro->total_fees_base_tti = $seguro->SCF;

    return $seguro;
  }

  static function getTarifas($fecha,$id_promocion,$id_cliente = '0',$id_agencia = '0',$id_comisionista = '0',$id_oficina = '0',$id_plaza = '0',$sub_tarifa = '0')
  {
    $tarifas = [];
    if($id_promocion != '0')
    {
      $tarifas_promocion = DB::select("SET DATEFORMAT MDY;
      SELECT *
       FROM dbo.WebTarifasMatriz
       WHERE Bloqueado = 0 AND IDTarifa IN (SELECT IDTarifa FROM dbo.WebCodigoDescuento WHERE IDCodDescuento = '$id_promocion' AND CAST(FechaValidezIni AS date) <= '$fecha' AND CAST(FechaValidezFin AS date) >= '$fecha')
       AND FechaMatriz = '$fecha'");
       if($tarifas_promocion != null)
       {
         foreach ($tarifas_promocion as $tarifa)
         {
           array_push($tarifas,$tarifa);
         }
       }
    }
    if($id_cliente != '0')
    {
      $tarifas_cliente = DB::select("SET DATEFORMAT MDY;
      SELECT *
       FROM dbo.WebTarifasMatriz
       WHERE Bloqueado = 0 AND IDTarifa IN (SELECT IDTarifa FROM dbo.WebClienteLeal WHERE IDCteLeal = '$id_cliente')  AND CAST(FechaMatriz AS date) = '$fecha'");
       if($tarifas_cliente != null)
       {
         foreach ($tarifas_cliente as $tarifa)
         {
           array_push($tarifas,$tarifa);
         }
       }
    }
    if($id_agencia != '0' )
    {
      $tarifas_agencia = null;
      if($id_plaza != '0')
      {
        $tarifas_agencia = DB::select("SET DATEFORMAT MDY;
        SELECT *
         FROM dbo.WebTarifasMatriz
         WHERE Bloqueado = 0 AND IDTarifa IN (SELECT IDTarifa FROM dbo.WebAgenciaTarifas WHERE IDAgencia = '$id_agencia' AND IDPlaza = '$id_plaza')  AND CAST(FechaMatriz AS date) = '$fecha'");
      }

      if($tarifas_agencia == null)
      {
        $tarifas_agencia = DB::select("SET DATEFORMAT MDY;
        SELECT *
         FROM dbo.WebTarifasMatriz
         WHERE Bloqueado = 0 AND IDTarifa IN (SELECT IDTarifa FROM dbo.WebAgencia WHERE IDAgencia = '$id_agencia')  AND CAST(FechaMatriz AS date) = '$fecha'");
      }
       if($tarifas_agencia != null)
       {
         foreach ($tarifas_agencia as $tarifa)
         {
           array_push($tarifas,$tarifa);
         }
       }
    }
    if($id_comisionista != '0' && $sub_tarifa != '0')
    {
      $tarifas_comisionista = DB::select("SET DATEFORMAT MDY;
      SELECT *
       FROM dbo.WebTarifasMatriz
       WHERE Bloqueado = 0 AND IDTarifa IN (SELECT IDTarifa FROM dbo.WebComisionista WHERE IDComisionista = '$id_comisionista')  AND CAST(FechaMatriz AS date) = '$fecha' AND SubTarifa = '$sub_tarifa' ");
       if($tarifas_comisionista != null)
       {
         foreach ($tarifas_comisionista as $tarifa)
         {
           array_push($tarifas,$tarifa);
         }
       }
    }
    if ($id_comisionista != '0')
    {
      $tarifas_comisionista = DB::select("SET DATEFORMAT MDY;
      SELECT *
       FROM dbo.WebTarifasMatriz
       WHERE Bloqueado = 0 AND IDTarifa IN (SELECT IDTarifa FROM dbo.WebComisionista WHERE IDComisionista = '$id_comisionista')  AND CAST(FechaMatriz AS date) = '$fecha'");
       if($tarifas_comisionista != null)
       {
         foreach ($tarifas_comisionista as $tarifa)
         {
           array_push($tarifas,$tarifa);
         }
       }
    }

    if($id_oficina != '0')
    {
      $tarifas_oficina = DB::select("SET DATEFORMAT MDY;
      SELECT *
       FROM dbo.WebTarifasMatriz
       WHERE Bloqueado = 0 AND IDTarifa IN (SELECT IDTarifa FROM WebOficina WHERE IDOficina = '$id_oficina')  AND CAST(FechaMatriz AS date) = '$fecha'");
       if($tarifas_oficina != null)
       {
         foreach ($tarifas_oficina as $tarifa)
         {
           array_push($tarifas,$tarifa);
         }
       }
    }
    if($id_plaza != '0')
    {
      $tarifas_plaza = DB::select("SET DATEFORMAT MDY;
      SELECT *
       FROM dbo.WebTarifasMatriz
       WHERE Bloqueado = 0 AND IDTarifa IN (SELECT IDTarifa FROM WebPlaza WHERE IDPlaza = '$id_plaza'  AND CAST(FechaMatriz AS date) = '$fecha')");
       if($tarifas_plaza != null)
       {
         foreach ($tarifas_plaza as $tarifa)
         {
           array_push($tarifas,$tarifa);
         }
       }
    }
    return $tarifas;
  }

  static function getOcupacion($id_plaza,$id_sipp_code,$fecha)
  {
    $ocupacion = new stdClass();

    $ocupacion->flota_total = DB::select("SET DATEFORMAT MDY;
    SELECT FlotaEstimada
    FROM dbo.WebPlazaMETAS
    WHERE IDPlaza = RTRIM(LTRIM('$id_plaza')) and IDSIPPCode=RTRIM(LTRIM('$id_sipp_code')) and  '$fecha'  BETWEEN CAST(FechaInicial AS date) and CAST(FechaFinal AS date)");

    $ocupacion->flota_reservas = DB::select("SET DATEFORMAT MDY;
    SELECT count(*) as NoReservas FROM dbo.WebReservas
    WHERE IDSIPPCode ='$id_sipp_code' AND '$fecha' between CAST(FechaReservacion AS date) and CAST(FechaRetorno AS date) and IDPlazaReservacion ='$id_plaza'");
    $ocupacion->flota_reservas = $ocupacion->flota_reservas[0]->NoReservas;


    $ocupacion->flota_contratos = DB::select("SET DATEFORMAT MDY;
    SELECT Count(*) as AutosEnContratos FROM dbo.Contratos
    WHERE IDPlaza = RTRIM(LTRIM('$id_plaza')) and IDSIPPCode=RTRIM(LTRIM('$id_sipp_code')) and  '$fecha'  BETWEEN CAST(FechaApertura AS date) and CAST(FechaRetorno AS date)");
    $ocupacion->flota_contratos = $ocupacion->flota_contratos[0]->AutosEnContratos;

    if($ocupacion->flota_total == null || $ocupacion->flota_total == 0 || $ocupacion->flota_total == '0')
    {
      $ocupacion->flota_total = 0;
      $ocupacion->porcentaje_ocupacion = 0;
    }
    else
    {
      $ocupacion->flota_total = intval($ocupacion->flota_total[0]->FlotaEstimada);
      $ocupacion->porcentaje_ocupacion = ($ocupacion->flota_reservas + $ocupacion->flota_contratos)/$ocupacion->flota_total;
    }
    return $ocupacion;
  }
  static function getCosto($porcentaje_ocupacion,$porcentaje_inferior,$porcentaje_superior,$costo_inferior,$costo_superior,$flota_total)
  {
    if($porcentaje_ocupacion <= $porcentaje_inferior)
    {
      $costo = $costo_inferior;
    }
    else if($porcentaje_ocupacion >= $porcentaje_superior)
    {
      $costo = $costo_superior;
    }
    else
    {
      $flota_dinamica = $flota_total - (($flota_total*$porcentaje_inferior) + ($flota_total*(1-$porcentaje_superior)));
      $factor_incremental = 1/$flota_total;
      $valor_incremental = ($costo_superior - $costo_inferior)/$flota_dinamica;
      $indice = (($porcentaje_ocupacion - $porcentaje_inferior) * $flota_total);//-1

      $costo = $costo_inferior + ($indice * $valor_incremental);
    }
    return $costo;
  }

  static function redondear_dos_decimal($valor)
  {
   $float_redondeado = round($valor * 100) / 100;
   return $float_redondeado;
  }

  static function getPlanCodeSeguros($id_cliente = '0',$id_agencia = '0',$id_comisionista = '0',$id_oficina = '0',$id_plaza = '0',$sipp_code = '0',$tarifa = '0')
  {

    if($tarifa != '0')
    {
      $idpcs = DB::select("SELECT * FROM dbo.WebPlanCodeSeguros WHERE IDPCS IN (SELECT IDPCS FROM dbo.WebClienteLeal WHERE IDCteLeal = '$id_cliente')");
       if($idpcs != null)
       {
           return $idpcs[0];
       }
    }

    if($id_cliente != '0')
    {
      $idpcs = DB::select("SELECT * FROM dbo.WebPlanCodeSeguros WHERE IDPCS IN (SELECT IDPCS FROM dbo.WebClienteLeal WHERE IDCteLeal = '$id_cliente')");
       if($idpcs != null)
       {
           return $idpcs[0];
       }
    }
    if($id_agencia != '0')
    {
      $idpcs = DB::select("SELECT * FROM dbo.WebPlanCodeSeguros WHERE IDPCS IN (SELECT IDPCS FROM dbo.WebAgencia WHERE IDAgencia = '$id_agencia')");
       if($idpcs != null)
       {
           return $idpcs[0];
       }
    }
    if($id_comisionista != '0')
    {
      $idpcs = DB::select("SELECT * FROM dbo.WebPlanCodeSeguros WHERE IDPCS IN (SELECT IDPCS FROM dbo.WebComisionista WHERE IDComisionista = '$id_comisionista')");
       if($idpcs != null)
       {
           return $idpcs[0];
       }
    }
    if($id_oficina != '0')
    {
      $idpcs = DB::select("SELECT * FROM dbo.WebPlanCodeSeguros WHERE IDPCS IN (SELECT IDPCS FROM dbo.WebOficina WHERE IDOficina = '$id_oficina')");
       if($idpcs != null)
       {
           return $idpcs[0];
       }
    }
    if($id_plaza != '0')
    {
      $idpcs = DB::select("SELECT * FROM dbo.WebPlanCodeSeguros WHERE IDPCS IN (SELECT IDPCS FROM dbo.WebPlaza WHERE IDPlaza = '$id_plaza')");
       if($idpcs != null)
       {
           return $idpcs[0];
       }
    }
    if($id_plaza != '0' && $sipp_code != '0')
    {
      $idpcs = DB::select("SELECT * FROM dbo.WebPlanCodeSeguros WHERE IDPCS IN (SELECT IDPCS FROM dbo.WebGrupoAutos
                           WHERE IDPlaza = '$id_plaza' AND IDSIPPCode1 = '$sipp_code')");
       if($idpcs != null)
       {
           return $idpcs[0];
       }
    }
    if($sipp_code != '0')
    {
      $idpcs = DB::select("SELECT * FROM dbo.WebPlanCodeSeguros WHERE IDPCS IN (SELECT IDPCS FROM dbo.WebSIPPCode WHERE IDSIPPCode = '$sipp_code')");
       if($idpcs != null)
       {
           return $idpcs[0];
       }
    }
    return null;
  }

  static function getPrecioCotizado($id_cliente,$id_agencia,$id_comisionista,$id_oficina,$id_plaza,$id_plaza_regreso,$es_fin_semana,$configuracion,$promocion,$iva,$edad,$fecha,$tiempo,$tarifa,$auto,$seguros,$precios)
  {
    $contador = 0;
    do
    {
      //Al principio sin error
      $error = 0;

      //Usamos la tarifa mandada y la usamos
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

        // Ponemos los precios del tk guardado
        $tarifa->TarifaMesTKInf = $precios->mes;
        $tarifa->TarifaSemanaTKInf = $precios->semana;
        $tarifa->TarifaDiaExtraTKInf = $precios->dia_extra;
        $tarifa->TarifaDiaTKInf = $precios->dia;
        $tarifa->TarifaFinSemanaTKInf = $precios->dia_fin_semana;
        $tarifa->TarifaHoraTKInf = $precios->hora;

        //Calculamos el precio con los parametros
        $auto_nuevo->costo = Calculos::calcularPrecioCotizado($fecha,$tiempo,$es_fin_semana,$tarifa,$configuracion,$id_plaza,$id_plaza_regreso,$promocion,$iva,$id_oficina,$tarifa->IDSIPPCode,$edad,$seguros,$precio_seguros);

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
        $auto_nuevo = new stdClass();
        //Si no, mandamos msg de error
        $auto_nuevo->costo = 'No definido';
      }

      //El contador es para cortar algun loop infinito
      $contador++;
    } while ($error == 1 && $contador < 20);
      return $auto_nuevo;
  }

  static function calcularPrecioCotizado($fecha,$tiempo, $es_fin_semana = false, $tarifa,$configuracion,$id_plaza,$id_plaza_retorno,$promocion,$iva,$id_oficina,$id_sipp_code,$edad,$seguros = 0,$precio_seguros)
  {

    $renta = new stdClass();
    $renta->meses_rentados = 0;
    $renta->semanas_rentadas = 0;
    $renta->dias_extras_rentados = 0;
    $renta->dias_rentados = 0;
    $renta->dias_fin_semana = 0;
    $renta->horas_rentadas = 0;

    $horas_por_dia = $configuracion->HorasxDia;
    $dias_por_semana = $tarifa->DiasXSemana;
    $dias_por_mes = $tarifa->DiasXMes;
    $descuentoPpgo =  1 - ($configuracion->PorDescuentoPpgo / 100);

    $semana = 7;
    $mes = 30;
    $dias_max_fin_semana = 4;
    $costo_drop_off = 0;

    $dias_temp = $tiempo->dias;
    $horas_temp = $tiempo->horas;

    $renta->meses_reales = floor($dias_temp/$mes);
    $dias_temp = $dias_temp%$mes;

    $renta->semanas_reales = floor($dias_temp/$semana);
    $dias_temp = $dias_temp%$semana;

    $renta->dias_reales = $dias_temp;
    $renta->horas_reales = $horas_temp;

    if($tiempo->horas >= 1)
    {
      $dias_seguros = $tiempo->dias + 1;
    }
    else
    {
      $dias_seguros = $tiempo->dias;
    }
    $renta->dias_seguros = $dias_seguros;
    $extra = false;

    if($tiempo->horas >= $horas_por_dia)
    {
      $tiempo->dias += 1;
      $tiempo->horas = 0;
    }
    $renta->horas_rentadas = $tiempo->horas;

    if($tiempo->dias == 0)
    {
      $tiempo->dias = 1;
      $tiempo->horas = 0;
    }

    $dias_TK = $tiempo->dias;
    $renta->dias_TK = $dias_TK;
    if($es_fin_semana && $tiempo->dias < $dias_max_fin_semana)
    {
      $renta->dias_fin_semana = $tiempo->dias;
    }
    else
    {
      if($tiempo->dias >= $dias_por_mes)
      {
        $renta->meses_rentados = floor($tiempo->dias/$dias_por_mes);
        $tiempo->dias = $tiempo->dias%$dias_por_mes;
        $extra = true;
        if($tiempo->dias >= $dias_por_mes && $tiempo->dias <= $mes)
        {
          $renta->meses_rentados += 1;
          $tiempo->dias = 0;
        }
      }

      if($tiempo->dias >= $dias_por_semana)
      {
        $renta->semanas_rentadas = floor($tiempo->dias/$semana);
        $tiempo->dias = $tiempo->dias%$semana;
        $extra = true;
        if($tiempo->dias >= $dias_por_semana && $tiempo->dias <= $semana)
        {
          $renta->semanas_rentadas += 1;
          $tiempo->dias = 0;
        }
      }

      if($extra)
      {
        $renta->dias_extras_rentados = $tiempo->dias;
      }
      else
      {
        $renta->dias_rentados = $tiempo->dias;
      }
    }

    if($tarifa->TipoTarifaAplicar != 'TK')
    {
      $seguros_tti = Calculos::getSeguros($renta,$tarifa,$id_plaza,$dias_seguros,$id_oficina,$seguros,$dias_TK,$edad,$precio_seguros);

      $total_extras = $seguros_tti->total_extras_tti / $seguros_tti->dias;
      $total_seguros = $seguros_tti->total_seguros_tti / $seguros_tti->dias;
      $total_fees_porcentaje = $seguros_tti->total_fees_porcentaje_tti;

      if($seguros_tti->tipoSCF == "D")
      {
        $total_extras = $total_extras + ($seguros_tti->total_fees_base_tti / $seguros_tti->dias);
      }

      $mes_tk = $tarifa->TarifaMesTTI;
      $semana_tk = $tarifa->TarifaSemanaTTI;
      $dias_extra_tk = $tarifa->TarifaDiaExtraTTI;
      $dia_tk = $tarifa->TarifaDiaTTI;
      $fin_semana_tk = $tarifa->TarifaFinSemanaTTI;

      //Desglose de IVA
      $mes_tk = $tarifa->TarifaMesTTI / $iva;
      $semana_tk = $tarifa->TarifaSemanaTTI / $iva;
      $dias_extra_tk = $tarifa->TarifaDiaExtraTTI / $iva;
      $dia_tk = $tarifa->TarifaDiaTTI / $iva;
      $fin_semana_tk = $tarifa->TarifaFinSemanaTTI / $iva;

      //Desglose de extras
      $mes_tk = $mes_tk - ($total_extras * $mes);
      $semana_tk = $semana_tk - ($total_extras * $semana);
      $dias_extra_tk = $dias_extra_tk - ($total_extras * 1);
      $dia_tk = $dia_tk - ($total_extras * 1);
      $fin_semana_tk = $fin_semana_tk - ($total_extras * 1);

      //Desglose de fees
      $mes_tk = $mes_tk / $total_fees_porcentaje;
      $semana_tk = $semana_tk / $total_fees_porcentaje;
      $dias_extra_tk = $dias_extra_tk / $total_fees_porcentaje;
      $dia_tk = $dia_tk / $total_fees_porcentaje;
      $fin_semana_tk = $fin_semana_tk / $total_fees_porcentaje;

      //Desglose de seguros
      $mes_tk = $mes_tk - ($total_seguros * $mes);
      $semana_tk = $semana_tk - ($total_seguros * $semana);
      $dias_extra_tk = $dias_extra_tk - ($total_seguros * 1);
      $dia_tk = $dia_tk - ($total_seguros * 1);
      $fin_semana_tk = $fin_semana_tk - ($total_seguros * 1);

      //Asignacion de Tarifas TK Inferior
      $tarifa->TarifaMesTKInf = $mes_tk;
      $tarifa->TarifaSemanaTKInf = $semana_tk;
      $tarifa->TarifaDiaExtraTKInf = $dias_extra_tk;
      $tarifa->TarifaDiaTKInf = $dia_tk;
      $tarifa->TarifaFinSemanaTKInf = $fin_semana_tk;
      $tarifa->TarifaHoraTKInf = $dia_tk / $horas_por_dia;

      //Asignacion de Tarifas TK Superior
      $tarifa->TarifaMesTKSup = $mes_tk;
      $tarifa->TarifaSemanaTKSup = $semana_tk;
      $tarifa->TarifaDiaExtraTKSup = $dias_extra_tk;
      $tarifa->TarifaDiaTKSup = $dia_tk;
      $tarifa->TarifaFinSemanaTKSup = $fin_semana_tk;
      $tarifa->TarifaHoraTKSup = $dia_tk / $horas_por_dia;

    }
    $tarifa->POInfTMes = $tarifa->POInfTMes / 100;
    $tarifa->POSupTMes = $tarifa->POSupTMes / 100;
    $tarifa->POInfTSemana = $tarifa->POInfTSemana / 100;
    $tarifa->POSupTSemana = $tarifa->POSupTSemana / 100;
    $tarifa->POInfTDiaExtra = $tarifa->POInfTDiaExtra / 100;
    $tarifa->POSupTDiaExtra = $tarifa->POSupTDiaExtra / 100;
    $tarifa->POInfTDia = $tarifa->POInfTDia / 100;
    $tarifa->POSupTDia = $tarifa->POSupTDia / 100;
    $tarifa->POInfTFinSemana = $tarifa->POInfTFinSemana / 100;
    $tarifa->POSupTFinSemana = $tarifa->POSupTFinSemana / 100;

    $renta->precio_por_mes = $tarifa->TarifaMesTKInf;
    $renta->precio_por_semana = $tarifa->TarifaSemanaTKInf;
    $renta->precio_por_dia_extra = $tarifa->TarifaDiaExtraTKInf;
    $renta->precio_por_dia = $tarifa->TarifaDiaTKInf;
    $renta->precio_por_dia_fin_semana =$tarifa->TarifaFinSemanaTKInf;
    $renta->precio_por_hora = $tarifa->TarifaHoraTKInf;

    $renta->costo_mes = $renta->precio_por_mes * $renta->meses_rentados;
    $renta->costo_semana = $renta->precio_por_semana* $renta->semanas_rentadas;
    $renta->costo_dia_extra = $renta->precio_por_dia_extra* $renta->dias_extras_rentados;
    $renta->costo_dia = $renta->precio_por_dia* $renta->dias_rentados;
    $renta->costo_dia_fin_semana = $renta->precio_por_dia_fin_semana* $renta->dias_fin_semana;
    $renta->costo_hora = $renta->precio_por_hora* $renta->horas_rentadas;

    $renta->seguros = Calculos::getSeguros($renta,$tarifa,$id_plaza,$dias_seguros,$id_oficina,$seguros,$dias_TK,$edad,$precio_seguros);

    $dia_reserva = date_create_from_format('m-d-Y',$fecha);

    $now = new DateTime('now');
    $dia_reserva = new DateTime(date_format($dia_reserva, 'Y-m-d'));

    $diff = $now->diff($dia_reserva);
    $diferencia_dia_reservacion = $diff->days;

    $renta->descuento_reserva_anticipada = 1;
    if($tarifa->FTDescPor > 0 && $diferencia_dia_reservacion >= $tarifa->FTDescPorDiasAntRes)
    {
      $renta->descuento_reserva_anticipada = 1 - ($tarifa->FTDescPor/100);
    }

    $ocupacion = Calculos::getOcupacion($id_plaza,$id_sipp_code,$fecha);

    $porcentaje_ocupacion = $ocupacion->porcentaje_ocupacion;

    if($tarifa->FTIncPor > 0 && $diferencia_dia_reservacion <= $tarifa->FTIncPorDiasAntRes && $porcentaje_ocupacion >= $tarifa->FTIncPorAplicarPO/100)
    {
      $renta->descuento_reserva_anticipada = 1 + ($tarifa->FTIncPor/100);
    }

    if($renta->seguros->status)
    {
      return $renta;
    }

    $renta->total_TK = ($renta->costo_mes + $renta->costo_semana + $renta->costo_dia_extra + $renta->costo_dia + $renta->costo_dia_fin_semana + $renta->costo_hora);
    $renta->total_TK_no_promo = ($renta->costo_mes + $renta->costo_semana + $renta->costo_dia_extra + $renta->costo_dia + $renta->costo_dia_fin_semana + $renta->costo_hora);

    //Ya teniendo el tk captamos el monto de los fees
    $renta->seguros->LCRF = round((($renta->seguros->LCRF/100) * ($renta->total_TK + $renta->seguros->total_seguros)),2);
    //$renta->seguros->AF = round((($renta->seguros->AF/100) * $renta->total_TK),2);
    //$renta->seguros->HF = round((($renta->seguros->HF/100) * $renta->total_TK),2);

    $renta->seguros->LCRF_tti = round((($renta->seguros->LCRF_tti/100) * ($renta->total_TK + $renta->seguros->total_seguros_tti)),2);
    //$renta->seguros->AF_tti = round((($renta->seguros->AF_tti/100) * $renta->total_TK),2);
    //$renta->seguros->HF_tti = round((($renta->seguros->HF_tti/100) * $renta->total_TK),2);

    $renta->total_seguros = $renta->seguros->total_seguros;
    $renta->porcentaje_fees = $renta->seguros->total_fees_porcentaje;
    $renta->fees_base = $renta->seguros->total_fees_base;
    $renta->total_extras = $renta->seguros->total_extras;
    $renta->iva = $iva;

    $renta->descuento_promo_global =  1;
    $renta->descuento_promo_TK =  1;
    $renta->descuento_promo_montoMN =  0;
    $renta->descuento_promo_dias =  0;

    $renta->P_descuento_promo_global =  1;
    $renta->P_descuento_promo_TK =  1;
    $renta->P_descuento_promo_montoMN = 0;
    $renta->P_descuento_promo_dias = 0;

    $monto = 0;
    $P_monto = 0;

    if(null != $promocion)
    {
      $renta->descuento_promo_global =  1 - ($promocion->PorcentajeGlobal/100);
      $renta->descuento_promo_TK =  1 - ($promocion->PorcentajeTK/100);
      $renta->descuento_promo_montoMN =  $promocion->MontoMN;
      $renta->descuento_promo_montoUSD = $promocion->MontoUSD;
      $renta->descuento_promo_dias =  $promocion->Dias;

      $renta->P_descuento_promo_global =  1 - ($promocion->P_PorcentajeGlobal/100);
      $renta->P_descuento_promo_TK =  1 - ($promocion->P_PorcentajeTK/100);
      $renta->P_descuento_promo_montoMN = $promocion->P_MontoMN;
      $renta->P_descuento_promo_montoUSD = $promocion->P_MontoUSD;
      $renta->P_descuento_promo_dias = $promocion->P_Dias;

      $renta->Moneda = $tarifa->Moneda;

      if($renta->Moneda == 'USD')
      {
        $monto = $renta->descuento_promo_montoUSD;
        $P_monto = $renta->P_descuento_promo_montoUSD;
      }
      else
      {
        $monto = $renta->descuento_promo_montoMN;
        $P_monto = $renta->P_descuento_promo_montoMN;
      }
    }

    $renta->Moneda = $tarifa->Moneda;

    if($id_plaza != $id_plaza_retorno)
    {
      $registro_costo_drop_off = DB::select("SELECT ImporteDropOffMN,ImporteDropOffUSD FROM dbo.[WebDropOff] WHERE IDPlazaRenta = '$id_plaza' AND IDPlazaRetorna = '$id_plaza_retorno' AND IDSIPPCode = '$id_sipp_code' AND CobraDropOff = '1'");
      if($registro_costo_drop_off != null)
      {
        $registro_costo_drop_off = $registro_costo_drop_off[0];
        if($renta->Moneda == 'USD')
        {
          $costo_drop_off = $registro_costo_drop_off->ImporteDropOffUSD;
        }
        else
        {
          $costo_drop_off = $registro_costo_drop_off->ImporteDropOffMN;
        }
      }
      else
      {
        $registro_costo_drop_off = DB::select("SELECT ImporteDropOffUSD FROM dbo.[WebDropOff] WHERE IDPlazaRenta = '$id_plaza' AND IDPlazaRetorna = '$id_plaza_retorno'AND CobraDropOff = '1'");
        if($registro_costo_drop_off != null)
        {
          $registro_costo_drop_off = $registro_costo_drop_off[0];
          if($renta->Moneda == 'USD')
          {
            $costo_drop_off = $registro_costo_drop_off->ImporteDropOffUSD;
          }
          else
          {
            $costo_drop_off = $registro_costo_drop_off->ImporteDropOffMN;
          }
        }
      }
    }


    $renta->seguros->monto_AF = ($renta->total_TK + $renta->total_seguros) * ($renta->seguros->AF/100);
    $renta->seguros->monto_HF = ($renta->total_TK + $renta->total_seguros) * ($renta->seguros->HF/100);

    $renta->drop_off = $costo_drop_off;

    $descuento_al_TK = 1 - (1 - $renta->descuento_reserva_anticipada) - ( 1 - $renta->descuento_promo_TK);
    $descuento_al_TK_prepago = 1 - (1 - $renta->descuento_reserva_anticipada) - ( 1 - $renta->P_descuento_promo_TK);

    if($renta->P_descuento_promo_global < 1)
    {
      $descuentoPpgo = $renta->P_descuento_promo_global;
    }

    $renta->subtotal_no_promo = ((($renta->total_TK_no_promo + $renta->total_seguros)* $renta->porcentaje_fees) + $renta->total_extras + $renta->fees_base) + $renta->drop_off;

    $renta->subtotal = ((((($renta->total_TK*$descuento_al_TK + $renta->total_seguros)* $renta->porcentaje_fees) + $renta->total_extras + $renta->fees_base) + $renta->drop_off) * $renta->descuento_promo_global) - $monto - ($renta->descuento_promo_dias * $renta->precio_por_dia);
    $renta->subtotal_prepago = ((((($renta->total_TK*$descuento_al_TK_prepago + $renta->total_seguros) * $renta->porcentaje_fees) + $renta->total_extras + $renta->fees_base)  + $renta->drop_off) * $descuentoPpgo)
    - $P_monto - ($renta->P_descuento_promo_dias * $renta->precio_por_dia);

    if($renta->subtotal < 0)
    {
      $renta->subtotal = 0;
    }
    if($renta->subtotal_prepago < 0)
    {
      $renta->subtotal_prepago = 0;
    }
    if($renta->subtotal_no_promo < 0)
    {
      $renta->subtotal_no_promo = 0;
    }

    $renta->total_no_promo = $renta->subtotal_no_promo * $iva;
    $renta->total = $renta->subtotal * $iva;
    $renta->prepago = $renta->subtotal_prepago * $iva;

    $renta->total = Calculos::redondear_dos_decimal($renta->total);
    $renta->prepago = Calculos::redondear_dos_decimal($renta->prepago);

    $renta->monto_iva_total = Calculos::redondear_dos_decimal($renta->total - $renta->subtotal);
    $renta->monto_iva_prepago = Calculos::redondear_dos_decimal($renta->prepago - $renta->subtotal_prepago);

    $renta->promocion_prepago = Calculos::redondear_dos_decimal($renta->subtotal_no_promo - $renta->subtotal_prepago);
    $renta->promocion_codigo = Calculos::redondear_dos_decimal($renta->subtotal_no_promo - $renta->subtotal);
    return $renta;
  }
}
