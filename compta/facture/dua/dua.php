
   <?php  // Other attributes

   
      
    $DCI =$_GET['DCI'];   
    $codaduana = $_GET['codaduana'];
    $anoprese = $_GET['anoprese'];

    $numecorre = $_GET['numecorre'];;



?>

<body>
  <script language="javascript">
 //// fvr : Este html en Aduanero debe estar en  : aduanas/aduanero/fiscalizacion/ildua.htm
 //// en los demas en  : /aduanas/informli/ildua.htm
    var gCount = 0;
 
function enviar() { 
    var m = document.consulta;     


/// fvr :27/12/2002 Aqui se direcciona a otro servlet para los regimenes Simplificados.No borrar...!!
      if ( document.consulta.n.value == '48'||document.consulta.n.value == 'DCX' ||document.consulta.n.value == '18' ||document.consulta.n.value == 'DCI') {
          document.consulta.cadu.value = document.consulta.codaduana.value;
      document.consulta.ndcl.value = document.consulta.numecorre.value;
          document.consulta.fano.value = document.consulta.anoprese.value;
          document.consulta.codr.value = document.consulta.n.value;
          document.consulta.action = 'http://www.sunat.gob.pe/servlet/SgCDUI18'; 
          }    
        document.consulta.submit(); 
}

////////////////
  </script>
 
<script language="javascript" src="http://www.sunat.gob.pe/aduanas/js/funcion.js"></script> 
<script language="javascript" src="http://www.sunat.gob.pe/aduanas/js/funcion.js"></script>
<div align="center">
 <link href="http://www.sunat.gob.pe/aduanas/css/standard.css" type="text/css" rel="stylesheet"><table width="95%" border="0">
  <tbody><tr>
   <script language="javascript">document.write(cab_pag("CONSULTA DE UNA DUA"))</script>     </tr></tbody>
    
 
    <form action="http://www.sunat.gob.pe/servlet/SgCDUI18" method="post" name="consulta">

    <table width="60%" border="0"> 
        <tr>
         <td width="50%" height="25">
            <select style='visibility:hidden' name="n" size="1">
                <?php echo "<option  value=".$DCI.">ImportaciónCourier </option>" ?>
            </select>

            <select style='visibility:hidden' name="codaduana" size="1">
  
                <?php echo "<option  value=".$codaduana.">235 Aerea del Callao </option>" ?>
            </select>

           <?php echo "<input type='hidden' name='anoprese' value=".$anoprese." type='text'>" ?>
 

            
            <?php echo "<input type='hidden' name='numecorre' value=".$numecorre.">" ?>
         </td>
        </tr>
    </table>

    <div align="right">
        <p align="center">
        <input name="option" value="una" type="hidden"> 
        <input name="cadu" value="235" type="hidden"> 
        <input name="ndcl" value="389835" type="hidden"> 
        <input name="codr" value="DCI" type="hidden"> 
        <input name="fano" value="2018" type="hidden"> 

        <input value="Consultar" onclick="enviar()" style="font-family: Verdana; font-size: 8pt; color: rgb(0,0,160)" tabindex="6" type="button">
        </p>
    </div>
     </form>
 </div>
</body>