@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/jquery-ui.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/hope-ui.min.css') }}" />
@endsection


@section('header')
    <h2>Registro de Cierre de Caja</h2>
    <p>Ingresar Cierre de Caja</p>
@endsection


@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <!-- <button class="btn btn-primary mb-4" id="btn-print">Imprimir</button> -->
                    <form class="mb-4" id="date-form">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Fecha</label>
                                    <input type="date" class="form-control"
                                        {{ auth()->user()->hasRole('Caja') ? 'readonly' : '' }} name="date"
                                        id="date"
                                        value="{{ request()->date ? request()->date : now()->format('Y-m-d') }}">
                                </div>
                                <div>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                        data-bs-target="#openCashModal">
                                        Abrir Caja Chica
                                    </button>

                                    <button type="submit" class="btn btn-secondary" id="btn-close-cash">
                                        <input type="hidden" name="closing_amount" id="closing_amount"
                                            value="{{ $total_egresos }}">
                                        <input type="hidden" name="cash_box_id" id="cash_box_id"
                                            value="{{ $cash_box_id }}">
                                        Cerrar Caja
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>


                    <label class="d-block my-4">Turno: {{ $shift == 0 ? 'Mañana' : 'Tarde' }}</label>
                    <div class="row justify-content-between">
                        <!-- Nueva fila con 2 columnas para la info principal y métodos de pago -->
                        <div class="row w-100">
                            <div class="col-md-6 d-flex align-items-stretch">
                                <div class="card bg-light h-100 w-100 mb-3">
                                    <div class="card-body text-center d-flex flex-column justify-content-center p-5">
                                        <h5 class="text-muted mb-3">MONTO ACTUAL EN CAJA</h5>
                                        <h1 class="display-2 text-success fw-bold mb-3">S/
                                            {{ number_format($total_ventas, 2) }}</h1>
                                        <p class="text-muted mb-1">Monto de apertura: S/ 0.00</p>
                                        <p class="text-muted mb-4">{{ now()->format('d/m/Y H:i:s') }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 d-flex align-items-stretch">
                                <div class="card h-100 w-100 mb-3">
                                    <div class="card-body d-flex align-items-center">
                                        <div class="w-100">
                                            <table class="table table-borderless" style="font-size: 1rem;">
                                                <tbody>
                                                    @foreach ($ventas_payment_methods as $payment_method)
                                                        <tr>
                                                            <td class="text-muted">
                                                                {{ ucfirst(strtolower($payment_method->name)) }} (S/)</td>
                                                            <td class="text-end fw-bold">S/
                                                                {{ number_format($payment_method->total, 2) }}</td>
                                                        </tr>
                                                    @endforeach

                                                    @php
                                                        // Consolidado por método: Ventas + Inicial + Pendientes
                                                        $totales_por_metodo = [];
                                                        foreach ($ventas_payment_methods as $pm) {
                                                            $nombre = ucfirst(strtolower($pm->nombre));
                                                            $totales_por_metodo[$nombre] =
                                                                ($totales_por_metodo[$nombre] ?? 0) + $pm->total;
                                                        }
                                                        $gran_total = array_sum($totales_por_metodo);
                                                    @endphp
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="row align-items-stretch justify-content-center mt-3">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card text-center w-100 mb-3 h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Propinas</h5>
                                    <div class="row mt-4">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">POS</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">{{ $pos_tips ?? 0.00 }}</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center w-100 mb-3 h-100">
                                <div class="card-body p-4">
                                    <h5 class="card-title">Comprobante de venta</h5>
                                    <div class="row mt-4">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Ticket</label>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Factura</label>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Boleta</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">{{ number_format($ticket_count, 0) }}</label>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">{{ number_format($factura_count, 0) }}</label>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">{{ number_format($boleta_count, 0) }}</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center w-100 mb-3 h-100">
                                <div class="card-body p-4">
                                    <h5 class="card-title">Egresos</h5>
                                    <div class="row mt-4">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Caja chica</label>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Egresos</label>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Saldo</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">{{ $caja_chica ?? 0.00}}</label>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">{{ $total_egresos ?? 0.00}}</label>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">{{ $saldo ?? 0.00}}</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="openCashModal" tabindex="-1" aria-labelledby="openCashModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="openCashModalLabel">Abrir Caja</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label>Monto Inicial:</label>
                    <input type="number" step="0.01" class="form-control" id="opening-amount" placeholder="0.00">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="btn-save-initial">Guardar cambios</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        const ConectorPluginV3 = (() => {

            /**
             * Una clase para interactuar con el plugin v3
             *
             * @date 2022-09-28
             * @author parzibyte
             * @see https://parzibyte.me/blog
             */

            class Operacion {
                constructor(nombre, argumentos) {
                    this.nombre = nombre;
                    this.argumentos = argumentos;
                }
            }

            class ConectorPlugin {

                static URL_PLUGIN_POR_DEFECTO = "http://localhost:8000";
                static Operacion = Operacion;
                static TAMAÑO_IMAGEN_NORMAL = 0;
                static TAMAÑO_IMAGEN_DOBLE_ANCHO = 1;
                static TAMAÑO_IMAGEN_DOBLE_ALTO = 2;
                static TAMAÑO_IMAGEN_DOBLE_ANCHO_Y_ALTO = 3;
                static TAMAÑO_IMAGEN_DOBLE_ANCHO_Y_ALTO = 3;
                static ALINEACION_IZQUIERDA = 0;
                static ALINEACION_CENTRO = 1;
                static ALINEACION_DERECHA = 2;
                static RECUPERACION_QR_BAJA = 0;
                static RECUPERACION_QR_MEDIA = 1;
                static RECUPERACION_QR_ALTA = 2;
                static RECUPERACION_QR_MEJOR = 3;


                constructor(ruta, serial) {
                    if (!ruta) ruta = ConectorPlugin.URL_PLUGIN_POR_DEFECTO;
                    if (!serial) serial = "";
                    this.ruta = ruta;
                    this.serial = serial;
                    this.operaciones = [];
                    return this;
                }

                CargarImagenLocalEImprimir(ruta, tamaño, maximoAncho) {
                    this.operaciones.push(new ConectorPlugin.Operacion("CargarImagenLocalEImprimir", Array.from(
                        arguments)));
                    return this;
                }
                Corte(lineas) {
                    this.operaciones.push(new ConectorPlugin.Operacion("Corte", Array.from(arguments)));
                    return this;
                }
                CorteParcial() {
                    this.operaciones.push(new ConectorPlugin.Operacion("CorteParcial", Array.from(arguments)));
                    return this;
                }
                DefinirCaracterPersonalizado(caracterRemplazo, matriz) {
                    this.operaciones.push(new ConectorPlugin.Operacion("DefinirCaracterPersonalizado", Array
                        .from(arguments)));
                    return this;
                }
                DescargarImagenDeInternetEImprimir(urlImagen, tamaño, maximoAncho) {
                    this.operaciones.push(new ConectorPlugin.Operacion("DescargarImagenDeInternetEImprimir",
                        Array.from(arguments)));
                    return this;
                }
                DeshabilitarCaracteresPersonalizados() {
                    this.operaciones.push(new ConectorPlugin.Operacion("DeshabilitarCaracteresPersonalizados",
                        Array.from(arguments)));
                    return this;
                }
                DeshabilitarElModoDeCaracteresChinos() {

                    this.operaciones.push(new ConectorPlugin.Operacion("DeshabilitarElModoDeCaracteresChinos",
                        Array.from(arguments)));
                    return this;
                }
                EscribirTexto(texto) {
                    this.operaciones.push(new ConectorPlugin.Operacion("EscribirTexto", Array.from(arguments)));
                    return this;
                }
                EstablecerAlineacion(alineacion) {
                    this.operaciones.push(new ConectorPlugin.Operacion("EstablecerAlineacion", Array.from(
                        arguments)));
                    return this;
                }
                EstablecerEnfatizado(enfatizado) {
                    this.operaciones.push(new ConectorPlugin.Operacion("EstablecerEnfatizado", Array.from(
                        arguments)));
                    return this;
                }
                EstablecerFuente(fuente) {
                    this.operaciones.push(new ConectorPlugin.Operacion("EstablecerFuente", Array.from(
                        arguments)));
                    return this;
                }
                EstablecerImpresionAlReves(alReves) {
                    this.operaciones.push(new ConectorPlugin.Operacion("EstablecerImpresionAlReves", Array.from(
                        arguments)));
                    return this;
                }
                EstablecerImpresionBlancoYNegroInversa(invertir) {
                    this.operaciones.push(new ConectorPlugin.Operacion("EstablecerImpresionBlancoYNegroInversa",
                        Array.from(arguments)));
                    return this;
                }
                EstablecerRotacionDe90Grados(rotar) {
                    this.operaciones.push(new ConectorPlugin.Operacion("EstablecerRotacionDe90Grados", Array
                        .from(arguments)));
                    return this;
                }
                EstablecerSubrayado(subrayado) {
                    this.operaciones.push(new ConectorPlugin.Operacion("EstablecerSubrayado", Array.from(
                        arguments)));
                    return this;
                }
                EstablecerTamañoFuente(multiplicadorAncho, multiplicadorAlto) {
                    this.operaciones.push(new ConectorPlugin.Operacion("EstablecerTamañoFuente", Array.from(
                        arguments)));
                    return this;
                }
                Feed(lineas) {
                    this.operaciones.push(new ConectorPlugin.Operacion("Feed", Array.from(arguments)));
                    return this;
                }
                HabilitarCaracteresPersonalizados() {
                    this.operaciones.push(new ConectorPlugin.Operacion("HabilitarCaracteresPersonalizados",
                        Array.from(arguments)));
                    return this;
                }
                HabilitarElModoDeCaracteresChinos() {
                    this.operaciones.push(new ConectorPlugin.Operacion("HabilitarElModoDeCaracteresChinos",
                        Array.from(arguments)));
                    return this;
                }
                ImprimirCodigoDeBarrasCodabar(contenido, alto, ancho, tamañoImagen) {

                    this.operaciones.push(new ConectorPlugin.Operacion("ImprimirCodigoDeBarrasCodabar", Array
                        .from(arguments)));
                    return this;
                }

                ImprimirCodigoDeBarrasCode128(contenido, alto, ancho, tamañoImagen) {
                    this.operaciones.push(new ConectorPlugin.Operacion("ImprimirCodigoDeBarrasCode128", Array
                        .from(arguments)));
                    return this;
                }
                ImprimirCodigoDeBarrasCode39(contenido, incluirSumaDeVerificacion, modoAsciiCompleto, alto, ancho,
                    tamañoImagen) {
                    this.operaciones.push(new ConectorPlugin.Operacion("ImprimirCodigoDeBarrasCode39", Array
                        .from(arguments)));
                    return this;
                }

                ImprimirCodigoDeBarrasCode93(contenido, alto, ancho, tamañoImagen) {
                    this.operaciones.push(new ConectorPlugin.Operacion("ImprimirCodigoDeBarrasCode93", Array
                        .from(arguments)));
                    return this;
                }

                ImprimirCodigoDeBarrasEan(contenido, alto, ancho, tamañoImagen) {
                    this.operaciones.push(new ConectorPlugin.Operacion("ImprimirCodigoDeBarrasEan", Array.from(
                        arguments)));
                    return this;
                }
                ImprimirCodigoDeBarrasEan8(contenido, alto, ancho, tamañoImagen) {
                    this.operaciones.push(new ConectorPlugin.Operacion("ImprimirCodigoDeBarrasEan8", Array.from(
                        arguments)));
                    return this;
                }
                ImprimirCodigoDeBarrasPdf417(contenido, nivelSeguridad, alto, ancho, tamañoImagen) {
                    this.operaciones.push(new ConectorPlugin.Operacion("ImprimirCodigoDeBarrasPdf417", Array
                        .from(arguments)));
                    return this;
                }
                ImprimirCodigoDeBarrasTwoOfFiveITF(contenido, intercalado, alto, ancho, tamañoImagen) {
                    this.operaciones.push(new ConectorPlugin.Operacion("ImprimirCodigoDeBarrasTwoOfFiveITF",
                        Array.from(arguments)));
                    return this;
                }
                ImprimirCodigoDeBarrasUpcA(contenido, alto, ancho, tamañoImagen) {
                    this.operaciones.push(new ConectorPlugin.Operacion("ImprimirCodigoDeBarrasUpcA", Array.from(
                        arguments)));
                    return this;
                }
                ImprimirCodigoDeBarrasUpcE(contenido, alto, ancho, tamañoImagen) {
                    this.operaciones.push(new ConectorPlugin.Operacion("ImprimirCodigoDeBarrasUpcE", Array.from(
                        arguments)));
                    return this;
                }
                ImprimirCodigoQr(contenido, anchoMaximo, nivelRecuperacion, tamañoImagen) {
                    this.operaciones.push(new ConectorPlugin.Operacion("ImprimirCodigoQr", Array.from(
                        arguments)));
                    return this;
                }
                ImprimirImagenEnBase64(imagenCodificadaEnBase64, tamaño, maximoAncho) {
                    this.operaciones.push(new ConectorPlugin.Operacion("ImprimirImagenEnBase64", Array.from(
                        arguments)));
                    return this;
                }

                Iniciar() {
                    this.operaciones.push(new ConectorPlugin.Operacion("Iniciar", Array.from(arguments)));
                    return this;
                }

                Pulso(pin, tiempoEncendido, tiempoApagado) {
                    this.operaciones.push(new ConectorPlugin.Operacion("Pulso", Array.from(arguments)));
                    return this;
                }

                TextoSegunPaginaDeCodigos(numeroPagina, pagina, texto) {
                    this.operaciones.push(new ConectorPlugin.Operacion("TextoSegunPaginaDeCodigos", Array.from(
                        arguments)));
                    return this;
                }


                static async obtenerImpresoras(ruta) {
                    if (ruta) ConectorPlugin.URL_PLUGIN_POR_DEFECTO = ruta;
                    const response = await fetch(ConectorPlugin.URL_PLUGIN_POR_DEFECTO + "/impresoras");
                    return await response.json();
                }

                static async obtenerImpresorasRemotas(ruta, rutaRemota) {
                    if (ruta) ConectorPlugin.URL_PLUGIN_POR_DEFECTO = ruta;
                    const response = await fetch(ConectorPlugin.URL_PLUGIN_POR_DEFECTO + "/reenviar?host=" +
                        rutaRemota);
                    return await response.json();
                }


                async imprimirEnImpresoraRemota(nombreImpresora, rutaRemota) {
                    const payload = {
                        operaciones: this.operaciones,
                        nombreImpresora,
                        serial: this.serial,
                    };
                    const response = await fetch(this.ruta + "/reenviar?host=" + rutaRemota, {
                        method: "POST",
                        body: JSON.stringify(payload),
                    });
                    return await response.json();
                }

                async imprimirEn(nombreImpresora) {
                    const payload = {
                        operaciones: this.operaciones,
                        nombreImpresora,
                        serial: this.serial,
                    };
                    const response = await fetch(this.ruta + "/imprimir", {
                        method: "POST",
                        // headers: {
                        //    'Content-Type': 'application/json; charset=utf-8'
                        // },
                        body: JSON.stringify(payload),
                    });
                    return await response.json();
                }
            }
            return ConectorPlugin;
        })();
    </script>
    <script>
        var serial = '{{ config('printer.serial') }}';
        var saldoEfectivo = {{ $efectivo ?? 0 }};
        var turno = {{ $turno ?? 0 }};
        var isDelivery = false;

        // Función de impresión reutilizable
        async function imprimirCierreCaja() {
            try {
                const IP_COMPUTADORA_REMOTA = "192.168.18.46"; // Cambiar por la IP de tu computadora con impresora
                const PUERTO_REMOTO = "8000";
                const URL_REMOTA = `http://${IP_COMPUTADORA_REMOTA}:${PUERTO_REMOTO}`;

                // Parámetros para ConectorPluginV3
                const licence = serial;
                const conector = new ConectorPluginV3(ConectorPluginV3.URL_PLUGIN_POR_DEFECTO, licence);
                await conector.Iniciar();


                // Función para crear encabezado común
                const crearEncabezado = (conector, fecha) => {
                    const ahora = new Date();
                    const fechaFormateada = ahora.getFullYear() + '-' +
                        String(ahora.getMonth() + 1).padStart(2, '0') + '-' +
                        String(ahora.getDate()).padStart(2, '0') + ' ' +
                        String(ahora.getHours()).padStart(2, '0') + ':' +
                        String(ahora.getMinutes()).padStart(2, '0');

                    return conector
                        .EstablecerTamañoFuente(1, 1) // Aumentar tamaño de fuente 2x ancho y 2x alto
                        .EstablecerAlineacion(1)
                        .EstablecerEnfatizado(true)
                        .EscribirTexto("De Cajón - Cierre de caja\n")
                        .EstablecerAlineacion(0)
                        .EscribirTexto(`Fecha: ${fecha}\n`)
                        .EscribirTexto(`Usuario: {{ auth()->user()->email }}\n`)
                        .EscribirTexto(`Turno: ${turno == 0 ? "Mañana" : "Tarde"}\n`)
                        .EscribirTexto(`F. impresion: ${fechaFormateada}\n`)
                        .EscribirTexto(`\n`)
                        .Feed(1)
                        .EscribirTexto("Met.: Total\n")
                        .EscribirTexto(`\n`);
                };

                // Función para agregar productos
                const agregarMontos = (impresionTexto) => {
                    document.querySelectorAll('table.table-bordered tbody tr').forEach(row => {
                        const celdas = row.querySelectorAll('td');
                        if (celdas.length > 0) {
                            let linea = '';
                            celdas.forEach(td => {
                                linea += td.innerText.trim() + '  '; // separador entre columnas
                            });

                            const primerValor = linea.split(" ")[0];

                            // Activar negrita para líneas importantes
                            if (primerValor === "TOTAL" || linea.includes("Efectivo") || primerValor ===
                                "Efectivo") {
                                impresionTexto = impresionTexto.EstablecerEnfatizado(true);
                            } else {
                                impresionTexto = impresionTexto.EstablecerEnfatizado(true);
                            }

                            impresionTexto = impresionTexto.EscribirTexto(linea.slice(0, -1).trim() + '\n');

                            if (primerValor === "TOTAL") {
                                impresionTexto = impresionTexto.EscribirTexto(
                                    "--------------------------------\n");
                            }
                        }
                    });
                    impresionTexto = impresionTexto.EscribirTexto('\n');
                    return impresionTexto;
                };

                // Función para crear pie de documento
                const crearPie = (impresionTexto, efectivo) => {
                    let textoValidez = "Efectivo = VEN + ANT - EGR";
                    // let real = parseFloat(document.getElementById('amount').value) || 0;
                    // let diferencia = real - efectivo;
                    let textoFinal = "";
                    return impresionTexto
                        .Feed(1)
                        .EstablecerTamañoFuente(1, 1) // Aumentar tamaño de fuente 2x ancho y 2x alto
                        .EstablecerAlineacion(1)
                        .EstablecerEnfatizado(true)
                        // .EscribirTexto(`Real     = S/${real.toFixed(2)}\n`)
                        // .EscribirTexto(`Diferen. = S/${diferencia.toFixed(2)}\n`)
                        .TextoSegunPaginaDeCodigos(2, "cp850", "Elaborado por Xinergia de Corporación XPANDE\n")
                        .Pulso(48, 60, 120)
                        .Corte(1);
                };

                // Función para obtener nombre de impresora según tipo
                const obtenerImpresora = () => {
                    return "Ticketera";
                };

                const imprimirDocumentoAutomatico = async (impresionTexto) => {
                    const nombreImpresora = obtenerImpresora();
                    const tipoDocumentoTexto = "cierre de caja"

                    let resultado = null;
                    let impresionLocal = false;
                    let impresionRemota = false;

                    try {
                        // PASO 1: Intentar impresión local primero
                        console.log('Intentando impresión local...');
                        resultado = await conector.imprimirEn(nombreImpresora);

                        if (resultado && resultado.ok) {
                            impresionLocal = true;
                            console.log('Impresión local exitosa');
                            ToastMessage.fire({
                                text: ` - ${tipoDocumentoTexto} impreso/a localmente`
                            })
                            return; // Salir si la impresión local fue exitosa
                        } else {
                            console.log('Impresión local falló, intentando remota...');
                        }
                    } catch (errorLocal) {
                        console.log('Error en impresión local:', errorLocal.message);
                    }

                    try {
                        // PASO 2: Si falla la impresión local, intentar remota
                        const urlRemotaCompleta = `${URL_REMOTA}/imprimir`;
                        resultado = await conector.imprimirEnImpresoraRemota(nombreImpresora,
                            urlRemotaCompleta);

                        if (resultado && resultado.ok) {
                            impresionRemota = true;
                            console.log('Impresión remota exitosa');
                            ToastMessage.fire({
                                text: ` - ${tipoDocumentoTexto} impreso/a remotamente (fallback)`
                            });
                            return; // Salir si la impresión remota fue exitosa
                        } else {
                            console.log('Impresión remota también falló');
                        }
                    } catch (errorRemoto) {
                        console.log('Error en impresión remota:', errorRemoto.message);
                    }

                    // PASO 3: Si ambas fallaron, mostrar error
                    const mensajeError = resultado && resultado.message ? resultado.message :
                        "No se pudo conectar con ninguna impresora";
                    ToastMessage.fire({
                        icon: 'warning',
                        text: ` - Error al imprimir ${tipoDocumentoTexto}: ${mensajeError}. Se intentó impresión local y remota.`
                    });
                };

                // Procesar según tipo de comprobante
                let impresionTexto;
                let tipoDocumentoCompleto = "cierre de caja";
                let tipoComprobante = "wincha";
                let fecha = document.getElementById('date').value;

                // Crear documento según tipo
                if (tipoComprobante === 'wincha') {
                    impresionTexto = crearEncabezado(conector, fecha);
                    impresionTexto = agregarMontos(impresionTexto);
                    impresionTexto = crearPie(impresionTexto, saldoEfectivo);
                    await imprimirDocumentoAutomatico(impresionTexto);
                } else {
                    // Tipo de comprobante no reconocido
                    ToastMessage.fire({
                        text: ' - Venta registrada sin impresión (tipo no reconocido)'
                    })
                }
            } catch (error) {
                console.error('Error al imprimir:', error);
                ToastMessage.fire({
                    icon: 'error',
                    text: 'Error al imprimir: ' + error.message
                });
            }
        }

        // Event listener para el botón imprimir
        document.getElementById('btnGuardar').addEventListener('click', async (event) => {
            event.preventDefault();
            await imprimirCierreCaja();
        });

        // Formulario de guardar cierre de caja
        $('#store-cash-close-form').on('submit', function(e) {
            e.preventDefault();

            spinner.classList.remove('spinner-visible');
            spinner.classList.add('spinner-hidden');

            let amount = $('#amount').val();
            let date = $('#date').val();

            $.ajax({
                url: "{{ route('cash_close.store') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    amount: amount,
                    date: date
                },
                success: function(response) {
                    if (response.status) {
                        ToastMessage.fire({
                            text: 'Cierre guardado correctamente'
                        });
                        $(`.table-responsive`).removeClass('d-none');
                        // Ejecutar impresión automáticamente después de guardar
                        setTimeout(() => {
                            imprimirCierreCaja();
                        }, 500);
                    } else {
                        ToastMessage.fire({
                            icon: 'error',
                            text: response.error || 'Error al guardar cierre'
                        });
                    }
                },
                error: function(xhr) {
                    ToastMessage.fire({
                        icon: 'error',
                        text: 'Error al guardar cierre'
                    });
                }
            }).always(function() {
                spinner.classList.add('spinner-hidden');
                spinner.classList.remove('spinner-visible');
            });
        });

        $('#date').on('change', function() {
            $('#date-form').submit();
        });
    </script>

    <script>
        $('#btn-save-initial').on('click', function() {
            const openingAmount = parseFloat($('#opening-amount').val()) || 0;

            if (openingAmount <= 0) {
                ToastError.fire({
                    title: 'Error',
                    text: 'El monto inicial debe ser mayor a cero.'
                });
                return;
            }

            $.ajax({
                url: "{{ route('cash_boxes.store') }}",
                method: 'POST',
                data: {
                    opening_amount: openingAmount,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.status) {
                        ToastMessage.fire({
                            text: response.message
                        });
                        location.reload();
                    } else {
                        ToastError.fire({
                            title: 'Error',
                            text: response.message
                        });
                    }
                },
                error: function(xhr, status, error) {
                    ToastError.fire({
                        title: 'Error',
                        text: xhr.responseJSON.message
                    });
                    console.error('Error:', xhr.responseJSON);
                }
            });
        });

        $('#btn-close-cash').on('click', function(e) {
            // Evitar que el botón tipo submit envíe el formulario
            e.preventDefault();

            ToastConfirm.fire({
                title: '¿Cerrar caja?',
                text: "¿Estás seguro de que deseas cerrar la caja?",
                icon: 'warning',
                confirmButtonText: 'Sí, cerrar',
                denyButtonText: 'Cancelar',
            }).then((result) => {
                if (result.isConfirmed) {
                    const closingAmount = parseFloat($('#closing_amount').val()) || 0;
                    const cashBoxId = $('#cash_box_id').val();

                    $.ajax({
                        url: "{{ url('cash_boxes') }}" + '/' + cashBoxId,
                        method: 'PUT',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: {
                            closing_amount: closingAmount,
                        },
                        success: function(response) {
                            if (response.status) {
                                ToastMessage.fire({
                                    text: response.message
                                });
                                // Llamar a la función cerrarCaja después de que el cierre fue exitoso
                                if (typeof cerrarCaja === 'function') {
                                    cerrarCaja();
                                }
                            } else {
                                ToastError.fire({
                                    title: 'Error',
                                    text: response.message
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            let msg = 'Error en la petición';
                            try {
                                msg = xhr.responseJSON && xhr.responseJSON.message ? xhr
                                    .responseJSON.message : msg;
                            } catch (err) {
                                // no-op
                            }
                            ToastError.fire({
                                title: 'Error',
                                text: msg
                            });
                            console.error('Error:', xhr.responseJSON || error);
                        }
                    });
                }
            });
        });
    </script>
@endsection
