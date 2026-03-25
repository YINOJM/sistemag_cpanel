<?php

require('./fpdf.php');

class PDF extends FPDF
{

   public $empresa;

   // Cabecera de página
   function Header()
   {
      include '../../modelo/conexion.php'; // llamados a la conexion BD

      if (!$this->empresa) {
          $consulta_info = $conexion->query(" select * from empresa ");
          $this->empresa = $consulta_info->fetch_object();
      }

      // LOGOS
      // Izquierdo: logo.png
      if (file_exists('logo.png')) {
          $this->Image('logo.png', 10, 6, 16); 
      }
      // Derecho: logo_1.png
      if (file_exists('logo_1.png')) {
         $this->Image('logo_1.png', 270, 6, 16);
      }

      // TEXTO CENTRADO
      $this->SetFont('Arial', 'B', 16);
      $this->SetTextColor(33, 37, 41);
      
      // Mover a la derecha para centrar respecto a los márgenes
      $this->Cell(0, 10, mb_convert_encoding($this->empresa->nombre, 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
      
      $this->SetFont('Arial', 'B', 14);
      $this->SetTextColor(108, 117, 125);
      $this->Cell(0, 10, mb_convert_encoding("REPORTE DE USUARIOS", 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
      
      $this->Ln(10); // Espacio antes de la tabla

      /* ENCABEZADOS DE TABLA */
      $this->SetFillColor(0, 119, 158); // Color Institucional (Teal #00779E)
      $this->SetTextColor(255, 255, 255);
      $this->SetDrawColor(180, 180, 180);
      $this->SetLineWidth(0.3);
      $this->SetFont('Arial', 'B', 9);
      
      // Anchos mejorados para ocupar ancho de A4 Landscape (~277mm útiles)
      // Total actual sumado: 10+25+30+40+40+60+30+30 = 265 (entra bien)
      $this->Cell(10, 10, mb_convert_encoding('N°', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', 1);
      $this->Cell(25, 10, mb_convert_encoding('TIPO', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', 1);
      $this->Cell(30, 10, mb_convert_encoding('DOCUMENTO', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', 1);
      $this->Cell(40, 10, mb_convert_encoding('NOMBRE', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', 1);
      $this->Cell(40, 10, mb_convert_encoding('APELLIDO', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', 1);
      $this->Cell(60, 10, mb_convert_encoding('CORREO', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', 1);
      $this->Cell(30, 10, mb_convert_encoding('USUARIO', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', 1);
      $this->Cell(30, 10, mb_convert_encoding('ROL', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', 1);
   }

   // Pie de página
   function Footer()
   {
      $this->SetY(-25); // Subir un poco el footer
      $this->SetFont('Arial', '', 8);
      $this->SetTextColor(128, 128, 128);

      // Línea separadora
      $this->Line(10, $this->GetY(), 287, $this->GetY());
      $this->Ln(3);

      // Información de empresa
      if($this->empresa) {
         $this->Cell(0, 4, mb_convert_encoding($this->empresa->ubicacion ?? 'Ubicación no registrada', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
         // Fecha de impresión en el pie de página en lugar del teléfono
         $hoy = date('d/m/Y H:i:s');
         $this->Cell(0, 4, mb_convert_encoding("Generado: " . $hoy, 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
      }

      // Paginación y Créditos
      $this->Ln(2);
      $this->SetFont('Arial', 'I', 8);
      $this->Cell(0, 4, mb_convert_encoding('Página ', 'ISO-8859-1', 'UTF-8') . $this->PageNo() . '/{nb}', 0, 0, 'C');
      
      // Crédito autor a la derecha (opcional, para consistencia)
      $this->SetX(-60);
      $this->Cell(50, 4, mb_convert_encoding('© Omar Jara Mendoza', 'ISO-8859-1', 'UTF-8'), 0, 0, 'R');
   }
}

include '../../modelo/conexion.php';
/* CONSULTA INFORMACION DEL HOSPEDAJE */

$pdf = new PDF();
$pdf->AddPage("L"); /* L = Landscape (horizontal) para más espacio */
$pdf->AliasNbPages(); //muestra la pagina / y total de paginas

$i = 0;
$pdf->SetFont('Arial', '', 9);
$pdf->SetDrawColor(200, 200, 200); //colorBorde gris claro

$consulta_reporte_usuario = $conexion->query(" select * from usuario ");

// Alternar colores de fila
$fill = false;

while ($datos_reporte = $consulta_reporte_usuario->fetch_object()) {

   $i = $i + 1;

   // Alternar color de fondo
   $pdf->SetFillColor(248, 249, 250); // Gris muy claro

   /* TABLA */
   $pdf->Cell(10, 10, mb_convert_encoding($i, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', $fill);
   $pdf->Cell(25, 10, mb_convert_encoding($datos_reporte->tipo_documento, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', $fill);
   $pdf->Cell(30, 10, mb_convert_encoding($datos_reporte->dni ? $datos_reporte->dni : '-', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', $fill);
   $pdf->Cell(40, 10, mb_convert_encoding($datos_reporte->nombre, 'ISO-8859-1', 'UTF-8'), 1, 0, 'L', $fill);
   $pdf->Cell(40, 10, mb_convert_encoding($datos_reporte->apellido, 'ISO-8859-1', 'UTF-8'), 1, 0, 'L', $fill);
   $pdf->Cell(60, 10, mb_convert_encoding($datos_reporte->correo ? $datos_reporte->correo : 'No registrado', 'ISO-8859-1', 'UTF-8'), 1, 0, 'L', $fill);
   $pdf->Cell(30, 10, mb_convert_encoding($datos_reporte->usuario, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', $fill);
   $pdf->Cell(30, 10, mb_convert_encoding($datos_reporte->rol, 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', $fill);

   // Alternar el fill
   $fill = !$fill;

}

$pdf->Output('ReporteUsuarios.pdf', 'I');//nombreDescarga, Visor(I->visualizar - D->descargar)
