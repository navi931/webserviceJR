<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \stdClass;
use \DateTime;
use \App\Validaciones;
use \App\Conversiones;
use Illuminate\Support\Facades\DB;
use \App\object_sorter;
use Response;

class IndexController extends Controller
{
    //Optimizado
    public function prueba(Request $request)
    {
      $cadena = $request['cadena'];
      return $request;
    }

    //Optimizado
    public function getPlaza(Request $request)
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

      //Buscar plazas y oficinas
      $plazas = DB::select("SELECT IDPlaza, SiglasInternacionales,Nombre FROM dbo.[WebPlaza]");
      $oficinas = DB::select("SELECT IDPlaza,Nombre,IDOficina,BWeb,latitud,longitud FROM dbo.[WebOficina]");
      $plazas_json = [];
      //Vamos oficina por oficina asiganandolas a las plazas
      foreach ($plazas as $plaza)
      {
        $plaza_temp = new stdClass();

        $plaza_temp->IDPlaza = $plaza->IDPlaza;
        $plaza_temp->SiglasInternacionales = $plaza->SiglasInternacionales;
        $plaza_temp->Nombre = $plaza->Nombre;
        $plaza_temp->oficinas = [];

        foreach ($oficinas as $oficina)
        {
          if($oficina->IDPlaza == $plaza_temp->IDPlaza)
          {
            if(1 == $oficina->BWeb)
            {
              $office = new stdClass();
              $office->Nombre =  $oficina->Nombre;
              $office->IDOficina =  $oficina->IDOficina;
              $office->latitud =  $oficina->latitud;
              $office->longitud =  $oficina->longitud;

              array_push($plaza_temp->oficinas, $office);
            }
          }
        }
        array_push($plazas_json, $plaza_temp);
      }
      return Response::json($plazas_json,201);
    }

    //Optimizado
    public function getHorario(Request $request)
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

      //Verificamos que obtenemos los parametros necesarios para trabajar
      if(!(isset($request['id']) && isset($request['date'])))
      {
        return Response::json('Parametros no encontrados por el verbo GET',400);
      }

      $id = $request['id'];
      $date = $request['date'];
      $day = date("w",strtotime($date)) + 1;
      $horario = DB::select("SELECT h00,h01,h02,h03,h04,h05,h06,h07,h08,h09,h10,h11,h12,h13,h14,h15,h16,h17,h18,h19,h20,h21,h22,h23
         FROM dbo.[WebHorario] where IDOficina = '$id' AND DiaSemana = '$day'");

      //Asignamos los horarios a un arreglo
      //En caso de erro mandamos todo 1
      if($horario != null)
      {
        $horario = $horario[0];
        $horario_arreglo = [];
        array_push($horario_arreglo,$horario->h00);
        array_push($horario_arreglo,$horario->h01);
        array_push($horario_arreglo,$horario->h02);
        array_push($horario_arreglo,$horario->h03);
        array_push($horario_arreglo,$horario->h04);
        array_push($horario_arreglo,$horario->h05);
        array_push($horario_arreglo,$horario->h06);
        array_push($horario_arreglo,$horario->h07);
        array_push($horario_arreglo,$horario->h08);
        array_push($horario_arreglo,$horario->h09);
        array_push($horario_arreglo,$horario->h10);
        array_push($horario_arreglo,$horario->h11);
        array_push($horario_arreglo,$horario->h12);
        array_push($horario_arreglo,$horario->h13);
        array_push($horario_arreglo,$horario->h14);
        array_push($horario_arreglo,$horario->h15);
        array_push($horario_arreglo,$horario->h16);
        array_push($horario_arreglo,$horario->h17);
        array_push($horario_arreglo,$horario->h18);
        array_push($horario_arreglo,$horario->h19);
        array_push($horario_arreglo,$horario->h20);
        array_push($horario_arreglo,$horario->h21);
        array_push($horario_arreglo,$horario->h22);
        array_push($horario_arreglo,$horario->h23);
      }
      else
      {
        $horario_arreglo = [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1];
      }

      return Response::json($horario_arreglo,201);
    }

    //Optimizado
    public function getGrupoCarros(Request $request)
    {
      //Checar request por algun sql injection
      $request_viejo = $request->all();
      $request = Conversiones::checkArray($request);
      $diferencia = array_diff($request, $request_viejo);
      if([] != $diferencia)
      {
        return Response::json("Caracteres especiales no soportados",400);
      }

      //verificar que mandaron token
      if(!isset($request['token']))
      {
        return Response::json('Parametros no encontrados por el verbo GET',400);
      }
      $token = $request['token'];

      //Verificar que el token que mandaron existe
      $count = DB::select("SELECT COUNT(Token) as numero FROM dbo.[WebToken] WHERE Token = '$token' AND GETDATE() < FechaCaducidad");
      if(null == $count)
      {
        return Response::json('token no valido',409);
      }
      $count = (int)$count[0]->numero;

      if($count < 1)
      {
        return Response::json('token no valido',409);
      }

      //Si me han mandando el SIPPCode regreso solo ese auto
      if(isset($request['sippcode']))
      {
        $sipp_code = $request['sippcode'];

        //Obtener la descripcion del auto
        $descripcion = DB::select("SELECT TOP 1 b.Descripcion,b.DescripcionEN,IDSIPPCode,Pasajeros,MaletasG,MaletasCh,Puertas,RendimientoLitro,Transmision,AireAcondicionado,OtrasCaracteristicas
        FROM dbo.[WebSIPPCode] as a, dbo.[WebGrupoAutos] as b WHERE IDSIPPCode = '$sipp_code' AND a.IDSIPPCode = b.IDSIPPCode1 AND b.Activo = 1");//Obtener las descripciones por el sippcode del carro

        if(null != $descripcion)
        {
          $descripcion = $descripcion[0];
          //0 = Estandar, 1 = Automática, 2 = Continua (Sin Cambios), 3 = "Estandar 4x4, 4 = Automatica 4x4, default = Sin dato
          switch ($descripcion->Transmision)
          {
            case 0:
              $descripcion->Transmision = "Estandar";
              break;
            case 1:
              $descripcion->Transmision = "Automática";
              break;
            case 2:
              $descripcion->Transmision = "Continua (Sin Cambios)";
              break;
            case 3:
              $descripcion->Transmision = "Estandar 4x4";
              break;
            case 4:
              $descripcion->Transmision = "Automatica 4x4";
              break;
            default:
              $descripcion->Transmision = "Sin dato";
              break;
          }
          if(1 == $descripcion->AireAcondicionado)
          {
            $descripcion->AireAcondicionado = "Si";
          }
          else
          {
            $descripcion->AireAcondicionado = "No";
          }
        return Response::json($descripcion,201);
       }
       else
       {
         return Response::json('No se encontro el carro',500);
       }
      }
      //Si me han mandando el id de la oficina buscar la plaza y regresar los carros
      //Si no me especifican regreasre todos
      if(isset($request['id']))
      {
        $id_oficina = $request['id'];
        //Obtener la oficina el id que me mandaron
        //$oficina = DB::select("SELECT IDPlaza FROM dbo.[WebOficina] WHERE IDOficina = '$id_oficina'")[0];

        //Obtener el IDPlaza de la Oficina
        //$id_plaza = $oficina->IDPlaza;

        //Obtener la descripcion del auto
        $descripciones = DB::select("SELECT b.Descripcion,b.DescripcionEN,IDSIPPCode,Pasajeros,MaletasG,MaletasCh,Puertas,RendimientoLitro,Transmision,AireAcondicionado,OtrasCaracteristicas
        FROM dbo.[WebSIPPCode] as a, dbo.[WebGrupoAutos] as b WHERE a.IDSIPPCode = b.IDSIPPCode1 AND b.Activo = 1 AND b.IDPlaza IN (SELECT IDPlaza FROM WebOficina WHERE IDOficina = '$id_oficina')
        ORDER BY Ordenamiento");                       //Obtener las descripciones por el sippcode del carro
        if(null != $descripciones)
        {
          foreach ($descripciones as $descripcion)
          {
            //0 = Estandar, 1 = Automática, 2 = Continua (Sin Cambios), 3 = "Estandar 4x4, 4 = Automatica 4x4, default = Sin dato
            switch ($descripcion->Transmision)
            {
              case 0:
                $descripcion->Transmision = "Estandar";
                break;
              case 1:
                $descripcion->Transmision = "Automática";
                break;
              case 2:
                $descripcion->Transmision = "Continua (Sin Cambios)";
                break;
              case 3:
                $descripcion->Transmision = "Estandar 4x4";
                break;
              case 4:
                $descripcion->Transmision = "Automatica 4x4";
                break;
              default:
                $descripcion->Transmision = "Sin dato";
                break;
            }
            if(1 == $descripcion->AireAcondicionado)
            {
              $descripcion->AireAcondicionado = "Si";
            }
            else
            {
              $descripcion->AireAcondicionado = "No";
            }
          }
          return Response::json($descripciones,201);
         }
         else
         {
           return Response::json('No se encontraron carros',500);
         }
      }
      else
      {
        //Obtener la descripcion del auto
        $descripciones = DB::select("SELECT b.Descripcion,b.DescripcionEN,IDSIPPCode,Pasajeros,MaletasG,MaletasCh,Puertas,RendimientoLitro,Transmision,AireAcondicionado,OtrasCaracteristicas
        FROM dbo.[WebSIPPCode] as a, dbo.[WebGrupoAutos] as b WHERE a.IDSIPPCode = b.IDSIPPCode1 AND b.Activo = 1
        ORDER BY Ordenamiento");
        if(null != $descripciones)
        {
          foreach ($descripciones as $descripcion)
          {
            switch ($descripcion->Transmision)
            {
              case 0:
                $descripcion->Transmision = "Estandar";
                break;
              case 1:
                $descripcion->Transmision = "Automática";
                break;
              case 2:
                $descripcion->Transmision = "Continua (Sin Cambios)";
                break;
              case 3:
                $descripcion->Transmision = "Estandar 4x4";
                break;
              case 4:
                $descripcion->Transmision = "Automatica 4x4";
                break;
              default:
                $descripcion->Transmision = "Sin dato";
                break;
            }
            if(1 == $descripcion->AireAcondicionado)
            {
              $descripcion->AireAcondicionado = "Si";
            }
            else
            {
              $descripcion->AireAcondicionado = "No";
            }
          }
          return Response::json($descripciones,201);
         }
         else
         {
           return Response::json('No se encontraron carros',500);
         }
      }
    }

    //Optimizado
    public function getPage(Request $request)
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

      $json_index = new stdClass();
      $json_index->menu = DB::select("SELECT M_ClvMenu,M_ClvPadre,M_Posicion,M_Url,M_Description,M_Descripcion FROM dbo.[MenusSQL] WHERE M_Habilitado = 1");
      $json_index->frames = DB::select("SELECT Direccion,NumFrame,URLDestino FROM dbo.[WebSitiosFrames] WHERE (FechaInicio<=getdate() AND getdate()<=FechaFin) AND EstatusFrame = 1");

      $this->array_sort_by($json_index->menu, 'M_ClvMenu');
      $this->array_sort_by($json_index->frames, 'NumFrame');

      return Response::json($json_index,201);
    }

    //Optimizado
    function array_sort_by(&$arrIni, $col, $order = SORT_ASC)
    {
        $arrAux = array();
        foreach ($arrIni as $key=> $row)
        {
            $arrAux[$key] = is_object($row) ? $arrAux[$key] = $row->$col : $row[$col];
            $arrAux[$key] = strtolower($arrAux[$key]);
        }
        array_multisort($arrAux, $order, $arrIni);
    }

    //Optimizado
    public function getEdades(Request $request)
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

      //Buscamos las edades validas y las regresamos
      $edades = DB::select("SELECT Edad,DescEdad,DescEdadUSA FROM dbo.WebEdad ORDER BY Edad DESC");
      if(null == $edades)
      {
        return Response::json('No se encontraron edades',500);
      }
      else
      {
        return Response::json($edades,201);
      }
    }

    //Optimizado
    public function validarUsuario(Request $request)
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
      if(!(isset($request['usuario']) && isset($request['password'])))
      {
        return Response::json("Parametros necesitados no enviados",400);
      }

      $user = $request['usuario'];
      $password = $request['password'];

      //Validamos usuario por nuestra validacion
      $usuario_validado = Validaciones::validarUsuario($user,$password);

      if(false != $usuario_validado)
      {
          return Response::json($usuario_validado,200);
      }
      else
      {
          return Response::json(false,200);
      }

      return Response::json("No se ha podido validar",500);
    }

    //Optimizado
    public function buscarCodigoDescuento(Request $request)
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

      //Verificamos los parametros enviados
      if(!isset($request['id_promocion']))
      {
        return Response::json('Favor de mandar id_promocion',400);
      }

      //Validamos el codigo con la libreria
      $id_promocion = $request['id_promocion'];
      $codigo = Validaciones::buscarCodigoDescuento($id_promocion);

      //Regresamos resultados
      if(null != $codigo)
      {
        return Response::json(true,200);
      }
      else
      {
        return Response::json(false,200);
      }
    }

    //Optimizado
    public function getSubtarifas(Request $request)
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
      if(!(isset($request['id_comisionista']) && isset($request['password'])))
      {
        return Response::json("Parametros necesitados no enviados",400);
      }

      $id_comisionista = $request['id_comisionista'];
      $password = $request['password'];

      //Validamos al usuario
      $usuario_validado = Validaciones::validarUsuario($id_comisionista,$password);

      if(false == $usuario_validado)
      {
          return Response::json('credenciales incorrectas',400);
      }

      //Busacar la tarifa principal
      $tarifa = DB::select("SELECT IDTarifa FROM WebComisionista WHERE IDComisionista = '$id_comisionista'");
      if($tarifa == null)
      {
        return Response::json('No se encontro tarifas',500);
      }
      $tarifa = $tarifa[0]->IDTarifa;

      //Con la tarifa principal buscamos las subtarifas
      $subtarifas = DB::select("SELECT SubTarifa FROM dbo.[WebTarifasBase] WHERE IDTarifa = '$tarifa'");

      //Las agregamos a un arreglo
      $subtarifas_temp = [];
      foreach ($subtarifas as $subtarifa)
      {
        array_push($subtarifas_temp, $subtarifa->SubTarifa);
      }

      //Regresamos el resultado
      if([] != $subtarifas_temp)
      {
        return Response::json($subtarifas_temp,200);
      }
      else
      {
        return Response::json('No se encontraron subtarifas',500);
      }
    }
}
