<!DOCTYPE html>
<html lang="es">
<style media="screen">
	h2{
    font-family: Sans-serif;
    font-size: 200%;
    font-weight: 775;
	}
  p{
    font-family: Sans-serif;
  }
	th,td{
		font-family: Sans-serif;
    text-align: center;
    font-weight: 100;
	}
  #descuento{
    color: green;
  }
  .primero{
    text-align: left;
  }
  #principal{
   box-shadow: 4px 10px 20px black;
   background-color: white;
   max-width: 800px;
   padding: 20px;
   margin: auto;
   border-collapse: collapse;
   opacity: 100%;
  }
  }
  #marca_agua{
    float: left;
  }
</style>
<head>
	<meta charset="utf-8">
	<title>Diiv</title>
</head>
<body id="body" style="background-color: #eeeeee;
														background-repeat: no-repeat;

														opacity: 95%;">

<div id="principal" style="box-shadow: 4px 10px 20px black;
														background-color: white;
														max-width: 800px;
														padding: 20px;
														margin: auto;
														border-collapse: collapse;
														opacity: 100%;">
        <!-- <img id="marca_agua" src='https://i.postimg.cc/XvtmMnj6/pedazo-de-figura-1.png' border='0' alt='pedazo-de-figura-1' style="width: 5%; height: 5%"/> -->
				<h2 style="color: black; margin: 0 0 7px;">
          Thank you for choosing Priceless
        </h2>
        <br>
				<p style="margin: 2px;">
					<b>The status of your reservation is:</b> {{$call->TipoMovRegistro}}
        </p>
				<p style="margin: 2px;">
					<b>Your Resevation is:</b> {{$call->IDReservacion}}
        </p>
        <br>
        <p style="margin: 2px;">
					<b>Name:</b>  {{$call->NombreCompleto}}
        </p>
        <br>
        <p style="margin: 2px;">
					<b>Phone:</b>  {{$call->Telefono}}
        </p>
        <br>
        <p style="margin: 2px;">
					<b>PickUp:</b>  {{$call->salida->Nombre}}, {{$call->salida->Calle}}, {{$call->salida->Telefono1}}, {{$call->salida->Telefono2}}.
        </p>
        <br>
        <p style="margin: 2px;">
					<b>DropOff:</b>  {{$call->regreso->Nombre}}, {{$call->regreso->Calle}}, {{$call->regreso->Telefono1}}, {{$call->salida->Telefono2}}.
        </p>
        <br>
        <p style="margin: 2px;">
					<b>Reservation Taken:</b> {{$call->FechaHoraOperacion}}
        </p>
        <br>
        <p style="margin: 2px;">
					<b>Vehicle Class:</b> {{$call->IDSIPPCode}}, {{$call->Descripcion}}
        </p>
				<p style="margin: 2px;">
					<b>Currency:</b> {{$call->Moneda}}
        </p>
        <br>
        <br><br>
        <table style="width:100%;font-size: 90%;">
				  <tr>
				    <th class="primero"></th>
				    <th></th>
				  </tr>
          <tr>
            <td class="primero"><b>TK:</b></td>
            <td>{{$call->TotalTK}}</td>
          </tr>
          <tr>
            <td class="primero"><b>Coberturas:</b></td>
            <td>{{$call->TotalCoberturas}}</td>
          </tr>
          <tr>
            <td class="primero"><b>Extras:</b></td>
            <td>{{$call->TotalExtras}}</td>
          </tr>
          <tr>
            <td class="primero"><b>Subtotal:</b></td>
            <td>{{$call->Subtotal}}</td>
          </tr>
          <tr>
            <td class="primero"><b>Taxes:</b></td>
            <td>{{$call->MontoIva}}</td>
          </tr>
          <tr>
            <td class="primero"><b>Total:</b></td>
            <td>{{$call->Total}}</td>
          </tr>
				</table>
				<div style="width: 100%; text-align: center">
					<!-- <a style="text-decoration: none; border-radius: 5px; padding: 11px 23px; color: white; background-color: #3498db" href="https://www.facebook.com/PokemonTrujillo/">Ir a la página</a> -->
				</div>
      </div>
</body>
</html>
