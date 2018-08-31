Módulo de Descuentos

Módulo para descuentos por línea de producción, basado en categorías unitarias de cada producto.


Ambiente de Ejecución

Los usuarios deben ingresar mediante ambiente web, a través de la URL provista por el organismo.


Plataforma de Programación / BBDD

Dolibarr 5.0.2
Apache/2.4.23 (Win32) OpenSSL/1.0.2h PHP/5.6.24
MySQL or MariaDB 5.5.5-10.1.16-MariaDB


Instalación del modulo

Este módulo se instala como cualquier otro, solo se necesita copiar la carpeta "descuento" y pegarla en la carpeta "htdocs" del dolibarr en uso, una vez terminado, Se debe acceder al apartado de administración de módulos en Dolibarr ubicado en Inicio -> Configuración -> Módulos (se requiere de una cuenta con permisos de administrador), desde ahí puede usted activar el módulo.


Ejecución inicial

Se debe acceder al apartado de configuración desde la ruta Inicio -> Configuración -> Descuento, una vez dentro nos localizaremos por defecto en la pestaña "Descuentos por línea de productos", dicha pestaña será de gran utilidad ya que todos los descuentos aplicados a presupuesto se gestionaran desde este apartado. En primera instancia se deberá especificar el cliente al cual se le aplicara el descuento, lo siguiente será agregar la categoría de los productos en cuestión junto a su porcentaje de descuento, para finalizar se deberá agregar una fecha inicial y final la cual nos indicara el periodo del descuento.
Para la aplicación del descuento se deberá crear un presupuesto, lo siguiente será agregar los productos derivados de la categoría que se vinculó en el apartado de configuración, una vez correctos tanto los productos como la información del presupuesto se deberá validar, una vez validado se mostraran los descuentos que se pueden aplicar, bastaría con hacer clic sobre el botón "Aplicar descuento", en caso de querer borrar algún descuento bastaría con hacer clic sobre el botón "Desvincular descuento"
