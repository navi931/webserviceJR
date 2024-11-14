<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use \DateTime;
use \stdClass;

class Conversiones
{
    static function getDaysHours($fecha_inicial,$fecha_final)
    {
      $fecha_inicial =  getdate(strtotime($fecha_inicial));
      $fecha_final =  getdate(strtotime($fecha_final));
      if($fecha_inicial[0] >= $fecha_final[0])
      {
        return 1;
      }

      $dia_fecha_inicial = $fecha_inicial['wday'];
      $dia_fecha_final = $fecha_final['wday'];

      $date1 = new DateTime($fecha_inicial['year'].'-'.$fecha_inicial['mon'].'-'.$fecha_inicial['mday']);
      $date2 = new DateTime($fecha_final['year'].'-'.$fecha_final['mon'].'-'.$fecha_final['mday']);
      $diff = $date1->diff($date2);
      $dias = $diff->days;                                                                                          //Obtenemos la diferencia en dias de la fecha

      $horas = $fecha_final['hours'] - $fecha_inicial['hours'];                                                     //Obtenemos la diferecnia en horas de la fecha
      if($horas<0)
      {
        $horas = 0;
      }

      $tiempo = new stdClass();

      if($fecha_inicial['mon']>=10)
      {
        $fecha_inicial_real = $fecha_inicial['mon'].'-'.$fecha_inicial['mday'].'-'.$fecha_inicial['year'].' '.$fecha_inicial['hours'].':'.$fecha_inicial['minutes'].':'.$fecha_inicial['seconds'].'.000';
        $fecha_inicial = $fecha_inicial['mon'].'-'.$fecha_inicial['mday'].'-'.$fecha_inicial['year'];
      }
      else
      {
        $fecha_inicial_real = '0'.$fecha_inicial['mon'].'-'.$fecha_inicial['mday'].'-'.$fecha_inicial['year'].' '.$fecha_inicial['hours'].':'.$fecha_inicial['minutes'].':'.$fecha_inicial['seconds'].'.000';
        $fecha_inicial = '0'.$fecha_inicial['mon'].'-'.$fecha_inicial['mday'].'-'.$fecha_inicial['year'];        //Armamos el string de la fecha para la base de datos
      }

      if($fecha_final['mon']>=10)
      {
        $fecha_final_real = $fecha_final['mon'].'-'.$fecha_final['mday'].'-'.$fecha_final['year'].' '.$fecha_final['hours'].':'.$fecha_final['minutes'].':'.$fecha_final['seconds'].'.000';
        $fecha_final = $fecha_final['mon'].'-'.$fecha_final['mday'].'-'.$fecha_final['year'];
      }
      else
      {
        $fecha_final_real = '0'.$fecha_final['mon'].'-'.$fecha_final['mday'].'-'.$fecha_final['year'].' '.$fecha_final['hours'].':'.$fecha_final['minutes'].':'.$fecha_final['seconds'].'.000';
        $fecha_final = '0'.$fecha_final['mon'].'-'.$fecha_final['mday'].'-'.$fecha_final['year'];        //Armamos el string de la fecha para la base de datos
      }

      $es_fin_semana = false;
      if($dia_fecha_inicial == 5 || $dia_fecha_inicial == 6 || $dia_fecha_inicial == 0)
      {
        if(($dia_fecha_final == 5 || $dia_fecha_final == 6 || $dia_fecha_final == 0 || $dia_fecha_final == 1) && $dias<=4)
        {
          $es_fin_semana = true;                                                                                     //Validamos que entra en fin de semana
        }
      }

      $tiempo->dias = $dias;
      $tiempo->horas = $horas;
      $tiempo->es_fin_semana = $es_fin_semana;
      $tiempo->fecha_inicial = $fecha_inicial;
      $tiempo->fecha_final = $fecha_final;
      $tiempo->fecha_inicial_real = $fecha_inicial_real;
      $tiempo->fecha_final_real = $fecha_final_real;
      return $tiempo;
    }

    static function getMillis()
    {
       list($Mili, $bot) = explode(" ", microtime());
       $DM = substr(strval($Mili),3,3);
       return $DM;
    }

    static function codeGenerator()
    {
      //Últimos dos numeros del año si es 2019 daria 19
      $year = (string)date('y');
      //Día del anio, 3 dígitos con ceros iniciales	0 365
      $day = (string)date('z');
      //Formato de 24 horas de una hora sin ceros iniciales	0 hasta 23
      $hours = date('G');
      //Minutos con ceros iniciales	00 hasta 59
      $minutes = date('i');
      //Segundos con ceros iniciales	00 hasta 59
      $seconds = date('s');
      //Tendremos el segundo del dia desde 0 hasta 86399
      $second_in_day = ($hours * 3600) + ($minutes * 60) + $seconds;
      //Obtenemos los milisegundos de 0 hasta 9 o sea, va de 100 millis en 100 millis
      $millis = (string)Conversiones::getMillis();
      //Juntamos
      $total = (int)($millis.$second_in_day.$day.$year);
      //Convertimos a base36 y regresamos
      $base36 = Conversiones::decimalABase36($total);

      while(strlen($base36) < 8)
      {
        $base36 = '0'.$base36;
      }
      return $base36;
    }
    static function decimalABase36($decimal)
    {
      //Al principio nuestro cociente es igual numero que nos mandaron
      $cociente = $decimal;
      //Creamos nuestro $diccionario de como vamos a interpretar nuestro sistema numerico en este caso sera base 36 combinando los numeros
      //con el alfabeto ingles

      $diccionario = [0 => '0',1 => '1',2 => '2',3 => '3',4 => '4',5 => '5',6 => '6',7 => '7',8 => '8',9 => '9',10 => 'A',11 => 'B',
                      12 => 'C',13 => 'D',14 => 'E',15 => 'F',16 => 'G',17 => 'H',18 => 'I',19 => 'J',20 => 'K',21 => 'L',22 => 'M',23 => 'N',
                      24 => 'O',25 => 'P',26 => 'Q',27 => 'R',28 => 'S',29 => 'T',30 => 'U',31 => 'V',32 => 'W',33 => 'X',34 => 'Y',35 => 'Z'];
      // $diccionario_hexa = [0 => '0',1 => '1',2 => '2',3 => '3',4 => '4',5 => '5',6 => '6',7 => '7',8 => '8',9 => '9',10 => 'A',11 => 'B',
      //                 12 => 'C',13 => 'D',14 => 'E',15 => 'F'];

      //Vemos que base es nuestro sistema decimal
      $base = count($diccionario);

      $residuos = [];
      //Calculamos los residuos
      do
      {
        $residuo = $cociente%$base;
        array_push($residuos,$residuo);
        $cociente = intval($cociente/$base);
      } while (0 < $cociente);

      //Formamos nuestro nuestro con la base que queramos y regresamos el resultado
      $resultado = '';
      foreach ($residuos as $residuo)
      {
        $resultado = $diccionario[$residuo].$resultado;
      }
      return $resultado;

    }
    static function tokenGenerator()
    {
      //Últimos dos numeros del año si es 2019 daria 19
      $year = (string)date('y');
      //Día del anio, 3 dígitos con ceros iniciales	0 365
      $day = (string)date('z');
      //Formato de 24 horas de una hora sin ceros iniciales	0 hasta 23
      $hours = date('G');
      //Minutos con ceros iniciales	00 hasta 59
      $minutes = date('i');
      //Segundos con ceros iniciales	00 hasta 59
      $seconds = date('s');
      //Tendremos el segundo del dia desde 0 hasta 86399
      $second_in_day = ($hours * 3600) + ($minutes * 60) + $seconds;
      //Obtenemos los milisegundos de 0 hasta 9 o sea, va de 100 millis en 100 millis
      $millis = (string)Conversiones::getMillis();
      // Se agregara un random de hasta 4 digitos
      $random = (string)rand(0,99999);
      //Juntamos
      $total = (int)($random.$millis.$second_in_day.$day.$year);
      //Convertimos a base36 y regresamos
      $base36 = Conversiones::decimalABase36($total);

      while(strlen($base36) < 11)
      {
        $base36 = '0'.$base36;
      }
      return $base36;
    }

    static function checkArray($array_to_check)
    {
      $array_to_check = $array_to_check->all();
      $array_checked = [];
      foreach ($array_to_check as $clave => $string_to_check)
      {
        $array_checked += ["$clave" => Conversiones::checkStringSql($string_to_check)];
      }
      return $array_checked;
    }

    static function checkStringSql($string_to_check)
    {
      $searchVal = array("&","|","=","<",">",";");
      $replaceVal = "";

      $string_checked = str_replace($searchVal, $replaceVal, $string_to_check);

      return $string_checked;
    }

    static function theBigger($value1,$value2)
    {
      $value1 = intval($value1);
      $value2 = intval($value2);
      if($value1 > $value2)
      {
        return $value1;
      }
      else
      {
        return $value2;
      }
    }

    static function codificar($cadena)
    {
      $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!#$%&/()=';
      $permitted_chars_length = strlen($permitted_chars);

      $random_char1 = $permitted_chars[mt_rand(0, $permitted_chars_length - 1)];
      $random_char2 = $permitted_chars[mt_rand(0, $permitted_chars_length - 1)];

      $cadena = $random_char1.$cadena.$random_char2;
      $codificado = base64_encode($cadena);
      $codificado = $random_char1.$codificado.$random_char2;
      return $codificado;
    }

    static function decodificar($codificado)
    {
      $codificado = substr($codificado,1,strlen($codificado));
      $codificado = substr($codificado,0,strlen($codificado)-1);
      $decodificado = base64_decode($codificado);
      $decodificado = substr($decodificado,1,strlen($decodificado));
      $decodificado = substr($decodificado,0,strlen($decodificado)-1);
      return $decodificado;
    }

}
