<template>
  <section class="estadisticas-sucursal-page q-pa-md">
    <div class="estadisticas-sucursal-hero q-pa-lg q-mb-lg">
      <div>
        <div class="text-caption text-uppercase text-weight-bold">
          Panel individual
        </div>

        <div class="text-h4 text-weight-bold">
          {{ nombreSucursal }}
        </div>

        <div class="text-subtitle1 q-mt-xs">
          Estadísticas detalladas de la sucursal seleccionada.
        </div>
      </div>

      <q-icon
        name="storefront"
        size="76px"
        class="estadisticas-sucursal-hero-icon"
      />
    </div>

    <q-card class="estadisticas-sucursal-filtros q-mb-lg">
      <q-card-section class="row items-center q-col-gutter-md">
        <div class="col-12 col-md-3">
          <q-select
            v-model="filtros.periodo"
            :options="opcionesPeriodo"
            emit-value
            map-options
            outlined
            dense
            label="Periodo"
            @update:model-value="manejarCambioPeriodo"
          />
        </div>

        <template v-if="filtros.periodo === 'personalizado'">
          <div class="col-12 col-sm-6 col-md-3">
            <q-input
              v-model="filtros.fechaDesde"
              type="date"
              outlined
              dense
              label="Fecha inicial"
            />
          </div>

          <div class="col-12 col-sm-6 col-md-3">
            <q-input
              v-model="filtros.fechaHasta"
              type="date"
              outlined
              dense
              label="Fecha final"
            />
          </div>
        </template>

        <div class="col-12 col-md-auto">
          <q-btn
            color="primary"
            icon="search"
            label="Consultar"
            unelevated
            :loading="cargando"
            :disable="!filtrosValidos"
            @click="cargarEstadisticas"
          />
        </div>

        <div class="col">
          <div class="row justify-end q-gutter-sm">
            <q-btn
              flat
              round
              icon="refresh"
              color="primary"
              :loading="cargando"
              @click="cargarEstadisticas"
            >
              <q-tooltip>
                Actualizar estadísticas
              </q-tooltip>
            </q-btn>

            <q-btn
              outline
              color="primary"
              icon="arrow_back"
              label="Volver"
              @click="volverDashboard"
            />
          </div>
        </div>
      </q-card-section>
    </q-card>

    <TarjetasMetricas
      :metricas="estadisticas.metricas"
      :cargando="cargando"
      class="q-mb-lg"
    />

    <GraficoVentas
      :titulo="`Ventas de ${nombreSucursal}`"
      :subtitulo="subtituloGrafico"
      :categorias="estadisticas.categorias"
      :series="estadisticas.series"
      :agrupacion="estadisticas.periodo.agrupacion"
      :cargando="cargando"
    />
  </section>
</template>

<script src="./EstadisticasSucursalView.js"></script>

<style src="./EstadisticasSucursalView.css" scoped></style>