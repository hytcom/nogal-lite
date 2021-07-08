# 2.9.7 - 202107081630
## bee
- cambios menores en el uso por línea de comandos

## files
- fix error en el método **ls** debido al cambio de la version 2.9.6

## nest
- se pasó de 16 a 32 caracteres en el tipo de campo **code**
- ahora se permite determinar el tipo de **index** para los campos **pid**

## rind
- el argumento **multiple** del comando **mergefile** ahora acepta **NUTS**. El **nut** es evaluado unicamente al momento de generar el **cache**
- se cambió la finalidad del método **InnerRind2php** hasta ahora en deshuso

## zip
- fix error en el método **cd** cuando el directorio es **..**

________________________________________________________________________________
# 2.9.6 - 202106031430
## general
- se añadió la constante **SID** con el id de la session actual
- se agregó el error 1000 en los **grafts** que indica la falta del objeto fuente

## coon
- se estableció la constante **NGL_ALVIN** como **key** cuando esta sea distinta de NULL
- se cambió el uso del método **fn:tokenDecode** por uno propio del objeto

## files
- cambio menor

## fn
- mensaje de error al no encontrar las funciones BCMATH

## rind
- se añadió de manera permanente a la constante **SID** como constante permitida

## shift
- mensaje de error al no encontrar el paquete YAML

## zip
- se agregaron los métodos **cd** y **pwd** para el manejo de directorios dentro del zip

________________________________________________________________________________
# 2.9.5 - 202105140030
## alvin
- fix error en **roleschain**

## bee
- se quitó la variable **_SESSION** del método **get** en el modo consola

## fn
- fix error de orden en **listToTree**

________________________________________________________________________________
# 2.9.4 - 202105041830
## bee
- se incluyeron las variables **_SESSION, _SERVER, _ENV y ENV** en el método **get**

## coon
- se añadió JSON_HEX_APOS en el método **request**

## owl
- nuevo método **alvinWhere**

________________________________________________________________________________
# 2.9.3 - 202104262130
## coon
- nuevos metodos **tokenEncode** y **tokenDecode**

## files
- fix en **copyr** si la carpeta de destino no existe es creada

## fn
- fix errores menores en **arrange** y **disarrange**

## unicode
- nuevo método **str_plit**

_______________________________________________________________________________
# 2.9.2 - 202104209000
## file
- se añadió la opción **CURLOPT_FOLLOWLOCATION** al método **read**

## mysql
- nuevo método **ifexists**
- se cambió chartset a **utf8mb4**

## nest
- fix error en el método **load** cuando no existe un registro **owl**
- cambios en **createFromYaml** ahora soporta el uso de **presets**
- fix errores de dependencia al borrar y renombrar
- **db2nest** ahora es **createNestCodeFromTable**
- comentarios y defaults en **CreateStructure**

## owl
- se agregaron opciones en el campo **roles** de la tabla **__ngl_owl_structure__** en el método **dbStructure**
- nuevo método **imyaOf**

## pgsql
- nuevo método **ifexists**

## rind
- fix error menor en **rindLoop**
- se quitó la codificación base64 para el método **file** del comando **set**. Ahora el usuario debe codificar el argumento antes de enviarlo

## tutor
- se añadió la clausula admin al método **alvin**
- ahora **Nulls** acepta **true** para procesar todos los argumentos

________________________________________________________________________________
# 2.9.1 - 202103111900
## coon
- se separó el **token** del tipo de **auth**
- nuevo argumento **bodyauth**
- nuevo argumento **port**

## fn
- se reescribieron los métodos **arrange** y **disarrange**
- se corrijieron errores de sitáxis en **tokenEndode** y **tokenDecode**
- nuevo método **parseHeaderProperty**

## mail
- cambios en el método **PrepareMails** y dependencias
- cambios en el método **Response** y dependencias
- nuevo argumento **peek**
- nuevo argumento **attachs_ignore**
- se mejoró la captura de adjuntos en el método **MailParser**

## owl
- fix error en el guardado del changelog

## root
- se reemplazó el **path** del archivo, por el **fullpath** en el manejador de errores

________________________________________________________________________________
# 2.9.0 - 202102161100
## genertal
- reestrucutración de samples

## alvin
- fix sintaxis en **rolechain**

________________________________________________________________________________
# 2.8.9 - 202102121800
## alvin
- fix sintaxis

## mail
- nuevo argumento **get_mode**
- cambios en los métodos **get**, **getall**, **getraw** y **getreplyto** para soporte de autologin
- nuevo método **MailParser**

## owl
- nuevos atributos **last_id** y **last_imya**

## shift
- nuevo método **jsObject**

________________________________________________________________________________
# 2.8.8 - 202102061400
## mail
- reestructuraciones generales
- cambios en los headers, nuevos métodos y la posibilidad de responder a un mail determniado

## rind
- se modificaron los métodos **stamp** y **SetPaths**, permitiendo ahora imprimir plantilas con rutas relativas al proyecto, desde cualquier ubicación

________________________________________________________________________________
# 2.8.7 - 202102021500
## fn
- nuevo método **arrayKeysR**
- se mejoraron los métodos **encoding** y **isArrayArray**

## mail
- se añadieron los atributos **getted**, **getted_id** y **getted_keys**
- mejora en el método **get**

## nest
- fix error en **DescribeColumns**git

## owl
- fix error en **insert**

________________________________________________________________________________
# 2.8.6 - 202101271800
## general
- se cambió el método alvin **reload** por **autoload** en los objetos **owl** y **rind**

## alvin
- el método **setGrant** ahora acepta 2 argumentos para la creación de un **grant**
- se renombró el método **reload** a **autoload**

## tree
- fix error en el método **childrenChain**

## tutor
- se activó el chequeo de permisos en el método **Alvin**

________________________________________________________________________________
# 2.8.5 - 202101252000
## alvin
- nuevos métodos **reload** y **AdminGrants**
- modificaciones a la estructura y salvado de los ROLES

## mysql
- nuevo método **describe**

## nest
- nuevos métodos **comment**, **db2nest** y **useroles**
- cambios en el manejo de ALTER TABLE

## owl
- cambios en los métodos **alvin** relacionados con el uso de ROLES

## rind
- cambio en la carga del token **alvin**

## tree
- fix error en **loadTree**

________________________________________________________________________________
# 2.8.4 - 202101201930
## alvin
- se establecieron como fijos los paths de los archivos en el filesystem, en el directorio **NGL_PATH_DATA/alvin**
- se terminaron de aplicar los roles

## fn
- fix errors en **arrayMultiSort**

## mail
- nuevos métodos **flag**, **unflag** y **BuildFlags**

## nest
- se modificó el método **CreateStructure** 

## owl
- nuevos métodos **dbStructure** y **ImyaFromID**
- se hicieron los cambios para la nueva estructura de roles
- cambios en la tabla y modo de logs

## tree
- nuevo método **childrenChain**
- se renombró el método **nodePath** a **parentsChain**
- se modificaron los métodos **get**, **parent**, **trace** y **children**

________________________________________________________________________________
# 2.8.3 - 202101071530
## general
- fix sintaxis por actualización a PHP 7.4

## alvin
- incorporación de roles (beta)

## fn
- fix error en **msort** derivado de la version 2.8.0

## nut
- fix en el segundo argumento del método **load**

## owl
- por error de un viejo debug, el método **query** ejecutaba dos veces la query

## pecker
- nuevo argumento **file_charset** en el método **loadfile**

## rind
- reestructuración del método **buildcache**
- se corrigió el uso de nut a raíz del fix de dicho objeto
- se mejoró el método **varName**

## tree
- nuevos métodos **get** y **nodePath**
- se el método **children**

_____________________________________________________
# 2.8.2 - 202012171600
## general
- fix sintaxis por actualización a PHP 7.4

________________________________________________________________________________
# 2.8.1 - 202012160000
## general
- se eliminaron redundancias de **isset** AND **empty**
- fix bugs relacionados con el cambio de sintaxis de la version 2.8.0

## mail
- fix bug

## nest
- cambios relacionados con código de las tablas

## owl
- se incorpora el concepto de código de tabla en **\_\_ngl\_owl\_structure\_\_**
- nuevo método **Imya** para que los **imyas** contengan ahora el código de la tabla
- nuevo método **getByImya**

## rind
- se mejoró la perfonmance de los loops owl

________________________________________________________________________________
# 2.8.0 - 202012030000
## general
- se agregó la *\\* de global namespace a todas las funciones y constantes PHP
- se modificó la estructura de **grafts**. Se instaló **composer** en esa carpeta y se eliminaron librerías obsoletas

________________________________________________________________________________
# 2.7.6 - 202011171600
## pecker
- organización del código

## owl
- fix join level

## tutor
- fix sanitizer

________________________________________________________________________________
# 2.7.5 - 202011161500
## pecker
- BETA finalizado

## owl
- cambio en sintaxis

## shift
- reemplazo de caracteres de control por **?** en el método **textTable**

________________________________________________________________________________
# 2.7.4 - 202011090000
## rind
- fix **userpwd** en readfile

## root
- se mejoró la performance en la carga del nucleo

## validate
- fix error en validación de enteros
 
________________________________________________________________________________
# 2.7.3 - 202011022100
## general
- nuevo método global **dumpc**
- fix firewall-ignore en el archivo alvin.php

## nest
- se añadieron los argumentos *enclosed*, *splitter* y *eol* en el método **write**
- fix error en **normalize**
- fix error en el nombre de los campos al crear
- fix error en los joins al utilizar **rename**

## pecker
- NUEVO OBJETO (beta)

## rind
- se añadió el argumento que determina el modo de validación alvin **alvin_mode**
- se añadió la posibilidad de recuperar un índice de un array en el comando **set**
- el método **vector** del comando **set** ya no requiere especificar el nombre de la columna

## root
- se integró **port** al método **currentpath**
- fix error en **inCurrentPath**

## shift
- reemplazo de tabulaciones por **\t** en el método **textTable**
________________________________________________________________________________
# 2.7.2 - 202009301030
## fn
- nuevo método **emptyToZero**

## nest
- se eliminó el log
- fix error en **alter**

## rind
- **mergefile** admite ahora que la ruta de los JSON en el modo *multiple* sean realtivas al proyecto

## root
- fix error en **errorMessage**

## tutor
- nuevo método **Sanitize**

________________________________________________________________________________
# 2.7.1 - 202009090200
## general
- actualización se samples

## fn
- nuevo método **msort**

## nest
- se añadió el concepto **foreign table** que permite vincular objetos de **owl** con otras tablas

## rind
- ahora el comando **alvin** retorna siempre **TRUE** cuando el nombre del usuario es **admin** y acepta variables $_SET
- cambio de salida en el comando **dump**, ahora utiliza el método global **dump**

________________________________________________________________________________
# 2.7.0 - 202009021200
## general
- cambios en la salida de errores, ahora es independiente para cada tipo de objeto
- nuevos métodos globales **dump** e **is**

## alvin
- cambios en la estructura y métodos para administrar y chequear los permisos, grupos y permisos [ACTUALIZACION CRITICA]
- se eliminó el método **GetGrant**
- nuevo método **profile**

## nest
- el método **collapse** fué reemplazado por **objectvar**
- fix error por campo *dependencies* en el método **generate**

## root
- nuevo método **is**
- cambio en **parseConfigString**, ahora se aceptan **\\** en las claves y **-** en las secciones

## shift
- se añadió el formato YAML al método **convert**

## trunk
- nuevo método **__errorMode__**, que establece el tipo de salida de error para el objeto

## unicode
- **is** es ahora **ischr**

## validate
- cambio de **is** por **ischr** en el método **ClearCharacters**

________________________________________________________________________________
# 2.6.0 - 202008181200
## dbase
- cambió la manera de leer los registros en el método **Fetch**
- nuevo método **handler**, que retorna el puntero de la conexión

## fn
- nuevo método **strToArray**

## mysql
- actualización de **mquery** y **mexec** por el cambio de **strToArray**
- nuevo método **handler**, que retorna el puntero de la conexión

## nest
- vuelta a MyISAM del motor en las tablas __ngl

## pgsql
- nuevo controlador para PostgreSQL

## pgsqlq
- nuevo controlador de resultados de PostgreSQL

## qparser
- se quitaron los slash para indicar el namespace global

## shift
- actualización de **fixedExplode** por el cambio de **strToArray**

## sqlite
- actualización de **mquery** y **mexec** por el cambio de **strToArray**
- nuevo método **handler**, que retorna el puntero de la conexión

## validate
- cambio en **RequestFrom** de **strToArray** por **explodeTrim**

________________________________________________________________________________
# 2.5.4 - 202008131630
## alvin
- se añadió Passhrase al encriptado del fuente de los permisos
- se redefinieron errores

## fn
- sandbox en **apacheMimeTypes**

## msqyl
- fix error en **mquery**

## nest
- cambio de motor InnoDB por MyISAM en las tablas __ngl

## rind
- fix error en el submetodo **ifempty**

## root
- cambio en el manejo de errores

________________________________________________________________________________
# 2.5.3 - 202008081430
## bee(cmd)
- se incorporó el argumento **-m** que permite ejecutar multiples comandos en el modo directo
- se incorporó el argumento **-s** que ejecuta los comandos en modo silecioso, sin generar output

## mysql
- cambios en el retorno de errores
- se mejoró el método **mquery**

## nest
- cambio en la validación de los argumentos **der** y **core**
- se añadió la funcionalidad de normalización automática al momento de crear un nuevo objeto
- se modificó la salida del log

## root
- fix error al escribir logs

## sqlite
- se aplicó **sandboxPath** a la ruta de la base de datos

________________________________________________________________________________
# 2.5.2 - 202008032030
## mysql
- mejora en **import**, cuando la tabla no existe, la primer línea del **CSV** es utilizada como nombres de las columnas y eliminada del contenido

## nest
- nuevo campo **dependencies** en la tabla **\_\_ngl_sentences\_\_**
  
## shift
- mejora en la conversión de *CSV*. Si el argumento **colnames** es **true** escribe en la primer línea las claves del array
  
________________________________________________________________________________
# 2.5.1 - 202008021930
## general
- variables de identificación en algunos objetos **feeder** del núcleo

## sysvar
- cambio de release por version

________________________________________________________________________________
# START
v2.5-20200730

## generalgit 
- Primera versión pública