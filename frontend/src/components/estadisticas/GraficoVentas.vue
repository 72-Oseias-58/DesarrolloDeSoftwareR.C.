  <template>
    <q-card class="grafico-card">
      <q-card-section class="row items-center justify-between q-pb-none">
        <div>
          <div class="text-h6 text-weight-bold">
            {{ titulo }}
          </div>

          <div v-if="subtitulo" class="text-caption text-grey-7 q-mt-xs">
            {{ subtitulo }}
          </div>
        </div>

        <q-icon name="show_chart" size="32px" color="primary" />
      </q-card-section>

      <q-card-section>
        <div v-if="cargando" class="grafico-estado">
          <q-spinner-dots color="primary" size="48px" />
          <div class="text-grey-7 q-mt-md">
            Cargando estadísticas...
          </div>
        </div>

        <div v-else-if="!tieneDatos" class="grafico-estado">
          <q-icon name="query_stats" size="56px" color="grey-5" />

          <div class="text-subtitle1 text-weight-medium q-mt-md">
            No hay ventas registradas
          </div>

          <div class="text-caption text-grey-7 q-mt-xs">
            El gráfico se actualizará cuando existan pagos confirmados.
          </div>
        </div>

        <VueApexCharts
          v-else
          type="line"
          height="380"
          :options="opcionesGrafico"
          :series="seriesGrafico"
        />
      </q-card-section>
    </q-card>
  </template>

  <script setup>
  import { computed } from 'vue'
  import VueApexCharts from 'vue3-apexcharts'

  const props = defineProps({
    titulo: {
      type: String,
      default: 'Ventas',
    },

    subtitulo: {
      type: String,
      default: '',
    },

    categorias: {
      type: Array,
      default: () => [],
    },

    series: {
      type: Array,
      default: () => [],
    },

    cargando: {
      type: Boolean,
      default: false,
    },

    agrupacion: {
      type: String,
      default: 'dia',
    },
  })

  const formatearCategoria = (categoria) => {
    if (!categoria) return ''

    if (props.agrupacion === 'hora') {
      return String(categoria).slice(11, 16)
    }

    if (props.agrupacion === 'dia') {
      const partes = String(categoria).split('-')

      if (partes.length === 3) {
        return `${partes[2]}/${partes[1]}`
      }
    }

    if (props.agrupacion === 'mes') {
      const partes = String(categoria).split('-')

      if (partes.length === 2) {
        return `${partes[1]}/${partes[0]}`
      }
    }

    return String(categoria)
  }

  const seriesGrafico = computed(() =>
    props.series.map((serie) => ({
      name: serie.nombre,
      data: Array.isArray(serie.datos) ? serie.datos : [],
    })),
  )

  const tieneDatos = computed(() => {
    return seriesGrafico.value.some((serie) =>
      serie.data.some((valor) => Number(valor) > 0),
    )
  })

  const opcionesGrafico = computed(() => ({
    chart: {
      type: 'line',
      toolbar: {
        show: true,
      },
      zoom: {
        enabled: true,
      },
      animations: {
        enabled: true,
        easing: 'easeinout',
        speed: 500,
      },
    },

    stroke: {
      curve: 'smooth',
      width: 3,
    },

    markers: {
      size: 4,
      hover: {
        size: 6,
      },
    },

    dataLabels: {
      enabled: false,
    },

    xaxis: {
      categories: props.categorias.map(formatearCategoria),
      labels: {
        rotate: -45,
        hideOverlappingLabels: true,
      },
    },

    yaxis: {
      min: 0,
      labels: {
        formatter: (valor) => `Bs ${Number(valor).toFixed(2)}`,
      },
    },

    tooltip: {
      shared: true,
      intersect: false,
      y: {
        formatter: (valor) => `Bs ${Number(valor).toFixed(2)}`,
      },
    },

    legend: {
      show: true,
      position: 'top',
      horizontalAlign: 'left',
    },

    grid: {
      borderColor: '#e0e0e0',
      strokeDashArray: 4,
    },

    noData: {
      text: 'No existen ventas para mostrar.',
    },

    responsive: [
      {
        breakpoint: 768,
        options: {
          chart: {
            height: 320,
          },

          legend: {
            position: 'bottom',
          },

          xaxis: {
            labels: {
              rotate: -60,
            },
          },
        },
      },
    ],
  }))
  </script>

  <style scoped>
  .grafico-card {
    border-radius: 18px;
    min-height: 470px;
  }

  .grafico-estado {
    min-height: 360px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
  }
  </style>