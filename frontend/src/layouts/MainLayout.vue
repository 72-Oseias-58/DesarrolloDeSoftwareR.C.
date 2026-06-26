<template>
  <q-layout view="lHh Lpr lFf" class="main-layout">
    <q-header elevated class="main-header">
      <q-toolbar>
        <q-btn
          flat
          dense
          round
          icon="menu"
          aria-label="Alternar menú"
          @click="alternarSidebar"
        >
          <q-tooltip>
            {{ miniSidebar ? 'Expandir menú' : 'Contraer menú' }}
          </q-tooltip>
        </q-btn>

        <q-toolbar-title>
          <div class="system-title">
            Sistema Rincón Chaqueño
          </div>
        </q-toolbar-title>

        <div class="user-info">
          <div class="user-name">{{ nombreUsuario }}</div>
          <div class="user-role">{{ rolUsuario }}</div>
        </div>

        <q-btn
          flat
          round
          :icon="$q.dark.isActive ? 'light_mode' : 'dark_mode'"
          @click="cambiarModoOscuro"
        >
          <q-tooltip>Cambiar modo</q-tooltip>
        </q-btn>

        <q-btn
          flat
          round
          icon="logout"
          color="negative"
          @click="cerrarSesion"
        >
          <q-tooltip>Cerrar sesión</q-tooltip>
        </q-btn>
      </q-toolbar>
    </q-header>

    <q-drawer
      v-model="drawer"
      show-if-above
      bordered
      :width="280"
      :mini="miniSidebar"
      :mini-width="84"
      class="main-drawer"
    >
      <div
        class="drawer-header"
        :class="{ 'drawer-header--mini': miniSidebar }"
      >
        <div class="drawer-logo">RC</div>

        <div class="drawer-identity q-mini-drawer-hide">
          <div class="drawer-title">Panel del Sistema</div>
          <div class="drawer-role">{{ rolUsuario }}</div>
        </div>

        <q-tooltip
          v-if="miniSidebar"
          anchor="center right"
          self="center left"
        >
          Panel del Sistema - {{ rolUsuario }}
        </q-tooltip>
      </div>

      <q-separator />

      <q-list padding class="drawer-menu">
        <template
          v-for="item in menuFiltrado"
          :key="item.label"
        >
          <q-item-label
            v-if="item.esTitulo && !miniSidebar"
            header
            class="text-weight-bold text-uppercase"
          >
            {{ item.label }}
          </q-item-label>

          <q-separator
            v-else-if="item.esTitulo && miniSidebar"
            spaced
            inset
          />

          <q-item
            v-else
            clickable
            v-ripple
            :to="item.to"
            :aria-label="item.label"
            active-class="menu-active"
            class="drawer-item"
          >
            <q-item-section avatar class="drawer-item-icon">
              <q-icon :name="item.icon" />
            </q-item-section>

            <q-item-section class="q-mini-drawer-hide">
              <q-item-label>{{ item.label }}</q-item-label>
              <q-item-label caption>{{ item.caption }}</q-item-label>
            </q-item-section>

            <q-item-section
              v-if="item.estado && !miniSidebar"
              side
            >
              <q-badge
                :color="item.estado === 'ACTIVA' ? 'green' : 'grey'"
                rounded
              />
            </q-item-section>

            <q-tooltip
              v-if="miniSidebar"
              anchor="center right"
              self="center left"
              :offset="[10, 0]"
            >
              <div class="text-weight-bold">{{ item.label }}</div>
              <div>{{ item.caption }}</div>
            </q-tooltip>
          </q-item>
        </template>

        <div
          v-if="cargandoSucursales"
          class="q-pa-md text-center"
        >
          <q-spinner-dots color="primary" size="32px" />
        </div>
      </q-list>
    </q-drawer>

    <q-page-container>
      <q-page class="main-page">
        <router-view />
      </q-page>
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { useMainLayout } from './MainLayout.js'

const {
  drawer,
  miniSidebar,
  nombreUsuario,
  rolUsuario,
  menuFiltrado,
  cargandoSucursales,
  alternarSidebar,
  cambiarModoOscuro,
  cerrarSesion,
} = useMainLayout()
</script>

<style scoped src="./MainLayout.css"></style>