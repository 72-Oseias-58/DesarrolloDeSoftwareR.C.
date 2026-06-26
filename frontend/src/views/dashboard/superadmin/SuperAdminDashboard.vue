<template>
  <section class="superadmin-page q-pa-md">
    <div class="superadmin-hero q-pa-lg q-mb-lg">
      <div>
        <div class="text-caption text-weight-bold text-uppercase">
          Panel de control
        </div>

        <h3 class="q-ma-none">
          Bienvenido, Superadmin
        </h3>

        <p class="q-mt-sm q-mb-none">
          Comparación general de ventas entre todas las sucursales.
        </p>
      </div>

      <q-icon
        name="admin_panel_settings"
        size="80px"
        class="hero-icon"
      />
    </div>

    <q-card class="filtros-card q-mb-lg">
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
              outlined
              dense
              type="date"
              label="Fecha inicial"
            />
          </div>

          <div class="col-12 col-sm-6 col-md-3">
            <q-input
              v-model="filtros.fechaHasta"
              outlined
              dense
              type="date"
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
          <div class="row justify-end">
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
      titulo="Ventas por sucursal"
      :subtitulo="subtituloGrafico"
      :categorias="estadisticas.categorias"
      :series="estadisticas.series"
      :agrupacion="estadisticas.periodo.agrupacion"
      :cargando="cargando"
      class="q-mb-lg"
    />

    <div class="row q-col-gutter-md">
      <div class="col-12 col-md-4">
        <q-card class="stat-card">
          <q-card-section>
            <q-icon
              name="storefront"
              size="42px"
              color="primary"
            />

            <div class="text-h5 q-mt-sm">
              {{ totalSucursales }}
            </div>

            <div class="text-grey-7">
              Sucursales registradas
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-4">
        <q-card class="stat-card">
          <q-card-section>
            <q-icon
              name="verified_user"
              size="42px"
              color="green"
            />

            <div class="text-h5 q-mt-sm">
              {{ sucursalesActivas }}
            </div>

            <div class="text-grey-7">
              Sucursales activas
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-4">
        <q-card class="stat-card">
          <q-card-section>
            <q-icon
              name="manage_accounts"
              size="42px"
              color="orange"
            />

            <div class="text-h5 q-mt-sm">
              {{ totalAdministradores }}
            </div>

            <div class="text-grey-7">
              Administradores
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </section>
</template>

<script src="./SuperAdminDashboard.js"></script>

<style src="./SuperAdminDashboard.css" scoped></style>