<template>
  <ToastNotification />
  <InvoiceTemplate v-if="printData" v-bind="printData" />
  <div class="flex h-screen bg-surface font-sans overflow-hidden print:hidden">

    <!-- ===== MOBILE OVERLAY ===== -->
    <transition name="fade">
      <div v-if="isMobileMenuOpen"
           class="fixed inset-0 z-40 bg-slate-900/70 backdrop-blur-sm md:hidden"
           @click="isMobileMenuOpen = false">
      </div>
    </transition>

    <!-- ===== SIDEBAR ===== -->
    <transition name="slide">
      <aside
        class="fixed inset-y-0 left-0 z-50 flex flex-col
               md:relative md:translate-x-0
               border-r border-white/5 transition-all duration-300 ease-in-out"
        :class="[
          isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
          isSidebarCollapsed ? 'w-20' : 'w-[268px]'
        ]"
        style="background: linear-gradient(160deg, #0f172a 0%, #1a1040 60%, #0f172a 100%);">

        <!-- Decorative top glow -->
        <div class="absolute top-0 left-0 right-0 h-64 pointer-events-none overflow-hidden rounded-b-3xl">
          <div class="absolute -top-20 -left-10 w-60 h-60 bg-brand-600/20 rounded-full blur-3xl"></div>
          <div class="absolute -top-10 right-0 w-40 h-40 bg-violet-600/10 rounded-full blur-3xl"></div>
        </div>

        <!-- ===== LOGO HEADER ===== -->
        <div class="relative z-10 px-5 pt-6 pb-5 flex items-center justify-between border-b border-white/5 overflow-hidden">
          <div class="flex items-center gap-3 transition-opacity duration-300" :class="isSidebarCollapsed ? 'opacity-0 invisible w-0' : 'opacity-100 visible w-full'">
            <!-- Logo mark -->
            <div class="relative">
              <div class="absolute -inset-1 bg-gradient-to-tr from-brand-500 to-violet-500 rounded-xl blur opacity-50"></div>
              <div class="relative w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-lg overflow-hidden p-1">
                <img src="/assets/logo.png" alt="Logo" class="w-full h-full object-contain">
              </div>
            </div>
            <!-- Brand text -->
            <div class="min-w-0">
              <h2 class="text-base font-bold text-white leading-none tracking-tight font-heading truncate">{{ companyShortName }}</h2>
              <p class="text-[9px] font-semibold tracking-[0.25em] uppercase mt-0.5"
                 style="color: #818cf8;">ERP PRO</p>
            </div>
          </div>
          <!-- Collapse Toggle (Desktop) -->
          <button @click="toggleSidebar"
                  class="hidden md:flex items-center justify-center p-2 rounded-xl text-slate-400 hover:text-white hover:bg-white/10 transition-all"
                  :class="isSidebarCollapsed ? 'mx-auto' : ''">
            <ChevronRightIcon class="w-5 h-5 transition-transform duration-500" :class="isSidebarCollapsed ? '' : 'rotate-180'" />
          </button>
          <!-- Mobile close -->
          <button @click="isMobileMenuOpen = false"
                  class="md:hidden p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-all">
            <XIcon class="w-4 h-4" />
          </button>
        </div>

        <!-- ===== NAVIGATION ===== -->
        <nav class="flex-1 px-3 py-4 overflow-y-auto sidebar-scrollbar space-y-0.5" @click="closeMobileOnNav">

          <!-- Worker view -->
          <template v-if="userRole === 'worker'">
            <SidebarSection label="Atelier" :collapsed="isSidebarCollapsed" />
            <SidebarLink href="/admin/atelier" :active="$page.url === '/admin/atelier'" :collapsed="isSidebarCollapsed">
              <UserCheckIcon class="w-4 h-4" />
              Atelier (Mobile)
            </SidebarLink>
          </template>

          <!-- Admin / Cashier view -->
          <template v-if="userRole === 'admin' || userRole === 'cashier'">

            <!-- Dashboard -->
            <SidebarLink href="/admin/dashboard" :active="$page.url === '/admin/dashboard'" featured :collapsed="isSidebarCollapsed">
              <LayoutGridIcon class="w-4 h-4" />
              Tableau de Bord
            </SidebarLink>

            <SidebarSection label="Opérations" :collapsed="isSidebarCollapsed" />

            <SidebarLink href="/admin/invoices" :active="$page.url === '/admin/invoices'" :collapsed="isSidebarCollapsed">
              <ReceiptIcon class="w-4 h-4" />
              Factures &amp; Devis
            </SidebarLink>
            <SidebarLink href="/admin/orders" :active="$page.url === '/admin/orders'" :collapsed="isSidebarCollapsed">
              <FileTextIcon class="w-4 h-4" />
              Ventes (POS)
            </SidebarLink>

            <!-- Caisse CTA special -->
            <a href="/pos"
               class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold mt-1 mb-1 transition-all duration-200 cursor-pointer overflow-hidden"
               :class="isSidebarCollapsed ? 'justify-center' : ''"
               style="background: linear-gradient(135deg, rgba(245,158,11,0.15), rgba(217,119,6,0.08)); border: 1px solid rgba(245,158,11,0.25); color: #fbbf24;">
              <div class="w-6 h-6 rounded-lg flex items-center justify-center shrink-0"
                   style="background: rgba(245,158,11,0.2);">
                <PlusCircleIcon class="w-3.5 h-3.5 text-amber-400" />
              </div>
              <span v-if="!isSidebarCollapsed">Ouvrir la Caisse</span>
              <ZapIcon v-if="!isSidebarCollapsed" class="w-3.5 h-3.5 ml-auto opacity-60 group-hover:opacity-100 transition-opacity" />
            </a>

            <SidebarLink href="/admin/workshop-queue" :active="$page.url === '/admin/workshop-queue'" :collapsed="isSidebarCollapsed">
              <ClipboardListIcon class="w-4 h-4" />
              File d'attente
            </SidebarLink>
            <SidebarLink href="/admin/atelier" :active="$page.url === '/admin/atelier'" :collapsed="isSidebarCollapsed">
              <UserCheckIcon class="w-4 h-4" />
              Atelier Mobile
            </SidebarLink>
            <SidebarLink href="/admin/workshop-stats" :active="$page.url === '/admin/workshop-stats'" :collapsed="isSidebarCollapsed">
              <BarChart3Icon class="w-4 h-4" />
              Stats Atelier
            </SidebarLink>

            <template v-if="userRole === 'admin'">
              <SidebarSection label="Inventaire &amp; Achats" :collapsed="isSidebarCollapsed" />

              <!-- Stock submenu -->
              <SidebarGroup icon="layers" label="Gestion de Stock"
                            :open="stockMenuOpen" :active="isStockActive"
                            :collapsed="isSidebarCollapsed"
                            @toggle="stockMenuOpen = !stockMenuOpen">
                <SidebarSubLink href="/admin/stock-mdf"   :active="$page.url === '/admin/stock-mdf'">Stock MDF / LATI</SidebarSubLink>
                <SidebarSubLink href="/admin/stock-canto" :active="$page.url === '/admin/stock-canto'">Stock Bandchant</SidebarSubLink>
              </SidebarGroup>

              <!-- Achats submenu -->
              <SidebarGroup icon="truck" label="Achats &amp; Fournisseurs"
                            :open="achatsMenuOpen" :active="isAchatsActive"
                            :collapsed="isSidebarCollapsed"
                            @toggle="achatsMenuOpen = !achatsMenuOpen">
                <SidebarSubLink href="/admin/achats"            :active="$page.url === '/admin/achats'">Réception Achats</SidebarSubLink>
                <SidebarSubLink href="/admin/achats-historique" :active="$page.url === '/admin/achats-historique'">Historique des Achats</SidebarSubLink>
                <SidebarSubLink href="/admin/fournisseurs"      :active="$page.url === '/admin/fournisseurs'">Fournisseurs &amp; Dettes</SidebarSubLink>
              </SidebarGroup>
            </template>

            <SidebarSection label="Relation Client" :collapsed="isSidebarCollapsed" />
            <SidebarLink href="/admin/clients" :active="$page.url === '/admin/clients'" :collapsed="isSidebarCollapsed">
              <UsersIcon class="w-4 h-4" />
              Clients &amp; Crédits
            </SidebarLink>

            <template v-if="userRole === 'admin'">
              <SidebarSection label="Ressources Humaines" :collapsed="isSidebarCollapsed" />

              <!-- HR submenu -->
              <SidebarGroup icon="hardhat" label="Gestion du Personnel"
                            :open="hrMenuOpen" :active="isHrActive"
                            :collapsed="isSidebarCollapsed"
                            @toggle="hrMenuOpen = !hrMenuOpen">
                <SidebarSubLink href="/admin/employees"  :active="$page.url === '/admin/employees'">Équipe &amp; Salaf</SidebarSubLink>
                <SidebarSubLink href="/admin/attendance" :active="$page.url === '/admin/attendance'">Pointage Quotidien</SidebarSubLink>
                <SidebarSubLink href="/admin/payroll"    :active="$page.url === '/admin/payroll'">Paie Hebdomadaire</SidebarSubLink>
              </SidebarGroup>

              <SidebarLink href="/admin/charges" :active="$page.url === '/admin/charges'" :collapsed="isSidebarCollapsed">
                <ReceiptIcon class="w-4 h-4" />
                Charges &amp; Dépenses
              </SidebarLink>

              <SidebarSection label="Configuration" :collapsed="isSidebarCollapsed" />
              <SidebarLink href="/admin/services"   :active="$page.url === '/admin/services'" :collapsed="isSidebarCollapsed">
                <SettingsIcon class="w-4 h-4" />
                Services &amp; Tarifs
              </SidebarLink>
              <SidebarLink href="/admin/settings"   :active="$page.url === '/admin/settings'" :collapsed="isSidebarCollapsed">
                <SlidersIcon class="w-4 h-4" />
                Paramètres
              </SidebarLink>
              <SidebarLink href="/admin/statistiques" :active="$page.url === '/admin/statistiques'" :collapsed="isSidebarCollapsed">
                <PieChartIcon class="w-4 h-4" />
                Statistiques
              </SidebarLink>
              <SidebarLink href="/admin/reports"    :active="$page.url === '/admin/reports'" :collapsed="isSidebarCollapsed">
                <FileTextIcon class="w-4 h-4" style="color: #a78bfa;" />
                Rapports PDF
              </SidebarLink>
              <SidebarLink href="/admin/system-logs" :active="$page.url === '/admin/system-logs'" :collapsed="isSidebarCollapsed">
                <ActivityIcon class="w-4 h-4" />
                Audit &amp; Activités
              </SidebarLink>
              <SidebarLink href="/admin/users"      :active="$page.url === '/admin/users'" :collapsed="isSidebarCollapsed">
                <ShieldIcon class="w-4 h-4" />
                Utilisateurs
              </SidebarLink>
              <SidebarLink href="/admin/backups"    :active="$page.url === '/admin/backups'" :collapsed="isSidebarCollapsed">
                <DatabaseIcon class="w-4 h-4" style="color: #34d399;" />
                Sauvegardes
              </SidebarLink>
            </template>
          </template>
        </nav>

        <!-- ===== USER FOOTER ===== -->
        <div class="relative z-10 p-3 border-t border-white/5">
          <!-- Logout row -->
          <div class="flex items-center gap-3 px-3 py-3 rounded-xl hover:bg-white/5 transition-all cursor-pointer group overflow-hidden">
            <!-- Avatar -->
            <div class="relative shrink-0">
              <img
                class="h-9 w-9 rounded-xl object-cover border-2 border-white/10 group-hover:border-brand-500/50 transition-colors"
                :src="'https://ui-avatars.com/api/?name=' + authUser.name + '&background=4F46E5&color=fff&bold=true'"
                :alt="authUser.name">
              <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-emerald-400 border-2 border-[#0f172a] rounded-full"></span>
            </div>
            <!-- Info -->
            <div class="flex-1 min-w-0 transition-opacity duration-300" :class="isSidebarCollapsed ? 'opacity-0 invisible w-0' : 'opacity-100 visible w-full'">
              <p class="text-sm font-semibold text-white truncate leading-tight">{{ authUser.name }}</p>
              <p class="text-[10px] font-medium text-slate-500 uppercase tracking-wider">{{ authUser.role }}</p>
            </div>
            <!-- Logout -->
            <button @click.stop="logout"
                    class="p-1.5 rounded-lg text-slate-500 hover:text-rose-400 hover:bg-rose-500/10 transition-all"
                    :class="isSidebarCollapsed ? 'hidden' : ''"
                    title="Déconnexion">
              <LogOutIcon class="w-4 h-4" />
            </button>
          </div>
        </div>

      </aside>
    </transition>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="flex-1 flex flex-col overflow-hidden relative min-w-0">

      <!-- Top Bar -->
      <header class="bg-white/80 backdrop-blur-xl border-b border-slate-200/60 px-5 py-3.5 flex justify-between items-center z-20 sticky top-0">
        <!-- Hamburger (mobile) -->
        <button @click="isMobileMenuOpen = true"
                class="md:hidden p-2.5 bg-slate-100 text-slate-600 rounded-xl hover:bg-slate-200 transition-all active:scale-95">
          <MenuIcon class="w-5 h-5" />
        </button>

        <!-- Page Name -->
        <div class="flex flex-col">
          <h2 class="text-base font-bold text-slate-900 leading-tight font-heading">{{ pageName }}</h2>
          <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest hidden sm:block">
            Dashboard / {{ pageName }}
          </p>
        </div>

        <!-- Right actions -->
        <div class="flex items-center gap-3">
          <!-- Search Trigger (Desktop) -->
          <button @click="searchRef?.open()" 
                  class="hidden md:flex items-center gap-3 px-4 py-2 rounded-xl bg-slate-100/60 border border-transparent 
                         hover:bg-white hover:border-slate-200 hover:shadow-sm text-slate-400 hover:text-slate-600 transition-all cursor-pointer">
            <SearchIcon class="w-4 h-4" />
            <span class="text-xs font-bold uppercase tracking-widest">Rechercher...</span>
            <span class="text-[9px] font-black border border-slate-200 px-1.5 py-0.5 rounded-lg opacity-40">Ctrl K</span>
          </button>

          <!-- Notifications -->
          <div class="relative" ref="notificationRef">
            <button @click="isNotificationsOpen = !isNotificationsOpen"
                    class="relative p-2.5 rounded-xl text-slate-500 bg-slate-100/60 border border-transparent
                           hover:bg-white hover:border-slate-200 hover:text-brand-600 hover:shadow-sm
                           transition-all duration-200 cursor-pointer">
              <BellIcon class="w-4.5 h-4.5 w-5 h-5" />
              <span v-if="notifications.length > 0"
                    class="absolute top-1.5 right-1.5 w-2 h-2 bg-rose-500 border-2 border-white rounded-full animate-pulse"></span>
            </button>

            <transition name="fade-slide">
              <div v-if="isNotificationsOpen"
                   class="absolute right-0 mt-3 w-80 bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden z-50">
                <div class="px-5 py-4 border-b border-slate-50 flex justify-between items-center bg-slate-50/50">
                  <h4 class="text-xs font-bold text-slate-900 uppercase tracking-widest font-heading">Alertes Système</h4>
                  <span v-if="notifications.length > 0"
                        class="px-2 py-0.5 bg-brand-50 text-brand-600 text-[10px] font-bold rounded-lg border border-brand-100">
                    {{ notifications.length }}
                  </span>
                </div>
                <div class="max-h-72 overflow-y-auto custom-scrollbar">
                  <div v-for="note in notifications" :key="note.id"
                       class="px-5 py-4 hover:bg-slate-50 border-b border-slate-50 transition-colors cursor-pointer">
                    <div class="flex gap-3">
                      <div :class="note.colorClass" class="w-9 h-9 rounded-xl flex-shrink-0 flex items-center justify-center">
                        <component :is="note.icon" class="w-4 h-4" />
                      </div>
                      <div>
                        <p class="text-xs font-semibold text-slate-800">{{ note.title }}</p>
                        <p class="text-[10px] text-slate-500 mt-0.5 leading-relaxed">{{ note.message }}</p>
                      </div>
                    </div>
                  </div>
                  <div v-if="notifications.length === 0" class="py-10 text-center text-slate-300">
                    <BellIcon class="w-10 h-10 mx-auto mb-2 opacity-30" />
                    <p class="text-xs font-semibold uppercase tracking-widest">Aucune notification</p>
                  </div>
                </div>
                <div v-if="notifications.length > 0" class="p-3 bg-slate-50 text-center border-t border-slate-100">
                  <button @click="notifications = []" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest hover:text-rose-500 transition-colors cursor-pointer">
                    Tout effacer
                  </button>
                </div>
              </div>
            </transition>
          </div>

          <div class="h-6 w-px bg-slate-200 mx-1"></div>

          <!-- User mini pill -->
          <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 cursor-default">
            <img :src="'https://ui-avatars.com/api/?name=' + authUser.name + '&background=4F46E5&color=fff&bold=true&size=32'"
                 class="w-6 h-6 rounded-lg" :alt="authUser.name">
            <span class="text-sm font-semibold text-slate-700 hidden sm:block">{{ authUser.name.split(' ')[0] }}</span>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-1 overflow-x-hidden overflow-y-auto bg-surface p-4 md:p-6">
        <transition name="page-fade" mode="out-in">
          <slot :key="url" />
        </transition>
      </main>
    </div>

    <!-- Modals & Globals -->
    <QuickSearch ref="searchRef" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, h, resolveComponent } from 'vue';
import axios from 'axios';
import { usePage, router, Link } from '@inertiajs/vue3';
import InvoiceTemplate from '@/Components/Print/InvoiceTemplate.vue';
import ToastNotification from '@/Components/ToastNotification.vue';
import QuickSearch from '@/Components/QuickSearch.vue';

const searchRef = ref(null);
const pageProps = usePage();
const url = computed(() => pageProps.url);

// ─── Print handler ──────────────────────────────────────────────
const printData = ref(null);
const handleGlobalPrint = (event) => {
  printData.value = event.detail;
  setTimeout(() => { window.print(); setTimeout(() => { printData.value = null; }, 500); }, 300);
};

// ─── Icons ──────────────────────────────────────────────────────
import {
  LayoutGridIcon, FileTextIcon, PlusCircleIcon, LayersIcon,
  UsersIcon, SettingsIcon, BellIcon, HardHatIcon, TruckIcon, ReceiptIcon,
  PieChartIcon, SlidersIcon, ChevronDownIcon, ActivityIcon, ShieldIcon,
  MenuIcon, XIcon, LogOutIcon, ChevronRightIcon, ClipboardListIcon,
  UserCheckIcon, BarChart3Icon, DatabaseIcon, ZapIcon, SearchIcon
} from 'lucide-vue-next';

// ─── Inline sub-components ──────────────────────────────────────

/** Section label */
const SidebarSection = {
  props: ['label', 'collapsed'],
  setup(props) {
    return () => h('div', {
      class: [
        'px-3 pt-5 pb-1.5 text-[9px] font-bold uppercase tracking-[0.2em] select-none transition-opacity duration-300',
        props.collapsed ? 'opacity-0 invisible h-0 py-0' : 'opacity-50'
      ].join(' '),
      style: 'color: rgb(148,163,184)'
    }, props.label)
  }
};

/** Nav link */
const SidebarLink = {
  props: { href: String, active: Boolean, featured: Boolean, collapsed: Boolean },
  setup(props, { slots }) {
    return () => h(Link, {
      href: props.href,
      class: [
        'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 cursor-pointer group',
        props.active
          ? 'text-white bg-brand-600 shadow-lg'
          : props.featured
            ? 'text-slate-300 hover:text-white hover:bg-white/8'
            : 'text-slate-400 hover:text-slate-200 hover:bg-white/6',
      ].join(' '),
      style: props.active ? 'box-shadow: 0 4px 14px rgba(79,70,229,0.4)' : ''
    }, () => {
      const children = slots.default?.() ?? [];
      const icon  = children[0];             // First child = icon VNode
      const label = children.slice(1);       // Rest = text node(s)
      return [
        h('div', {
          class: [
            'w-6 h-6 rounded-lg flex items-center justify-center shrink-0 transition-colors duration-200',
            props.active ? 'bg-white/20' : 'bg-white/5 group-hover:bg-white/10'
          ].join(' ')
        }, [icon]),
        h('span', { 
          class: [
            'flex-1 truncate transition-all duration-300',
            props.collapsed ? 'opacity-0 invisible w-0' : 'opacity-100 visible'
          ].join(' ')
        }, label)
      ];
    })
  }
};

/** Collapsible group */
const SidebarGroup = {
  props: { label: String, open: Boolean, active: Boolean, icon: String, collapsed: Boolean },
  emits: ['toggle'],
  setup(props, { slots, emit }) {
    const iconMap = { layers: LayersIcon, truck: TruckIcon, hardhat: HardHatIcon };
    return () => {
      const IconComponent = iconMap[props.icon] ?? LayersIcon;
      return h('div', { class: 'space-y-0.5' }, [
        h('button', {
          onClick: () => emit('toggle'),
          class: [
            'w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 cursor-pointer group',
            props.active || props.open ? 'text-slate-200 bg-white/8' : 'text-slate-400 hover:text-slate-200 hover:bg-white/6'
          ].join(' ')
        }, [
          h('div', {
            class: [
              'w-6 h-6 rounded-lg flex items-center justify-center shrink-0 transition-colors duration-200',
              props.active || props.open ? 'bg-white/20' : 'bg-white/5 group-hover:bg-white/10'
            ].join(' ')
          }, [h(IconComponent, { class: 'w-3.5 h-3.5' })]),
          h('span', { 
            class: [
              'flex-1 text-left truncate transition-all duration-300',
              props.collapsed ? 'opacity-0 invisible w-0' : 'opacity-100 visible'
            ].join(' ')
          }, props.label),
          h(ChevronDownIcon, {
            class: [
              'w-3.5 h-3.5 text-slate-500 transition-all duration-300', 
              props.open ? 'rotate-180' : '',
              props.collapsed ? 'opacity-0 invisible w-0' : 'opacity-100 visible'
            ].join(' ')
          })
        ]),
        props.open && !props.collapsed
          ? h('div', { class: 'pl-3 pr-1 pb-1 space-y-0.5' }, slots.default?.())
          : null
      ]);
    };
  }
};

/** Sub nav link */
const SidebarSubLink = {
  props: { href: String, active: Boolean },
  setup(props, { slots }) {
    return () => h(Link, {
      href: props.href,
      class: [
        'flex items-center gap-2.5 pl-4 pr-3 py-2 rounded-xl text-xs font-medium transition-all duration-200 cursor-pointer relative',
        props.active
          ? 'text-brand-300 bg-brand-600/15 border border-brand-500/20'
          : 'text-slate-500 hover:text-slate-300 hover:bg-white/5'
      ].join(' ')
    }, () => [
      h('span', {
        class: ['w-1.5 h-1.5 rounded-full shrink-0 transition-colors', props.active ? 'bg-brand-400' : 'bg-slate-600'].join(' ')
      }),
      slots.default?.()
    ])
  }
};

// ─── State ──────────────────────────────────────────────────────
const page = usePage();
const hrMenuOpen    = ref(false);
const stockMenuOpen = ref(false);
const achatsMenuOpen = ref(false);
const isSidebarCollapsed = ref(localStorage.getItem('sidebarCollapsed') === 'true');
const toggleSidebar = () => {
  isSidebarCollapsed.value = !isSidebarCollapsed.value;
  localStorage.setItem('sidebarCollapsed', isSidebarCollapsed.value);
};

const isMobileMenuOpen  = ref(false);
const isNotificationsOpen = ref(false);
const notifications = ref([]);
const notificationRef = ref(null);

// ─── Computed ───────────────────────────────────────────────────
const authUser    = computed(() => page.props.auth.user || { name: 'Utilisateur', role: 'cashier' });
const userRole    = computed(() => authUser.value.role);
const companyShortName = computed(() => (page.props.settings || {}).company_name || 'Mon Entreprise');

const isHrActive = computed(() =>
  ['/admin/employees', '/admin/attendance', '/admin/payroll'].some(p => page.url.includes(p)));
const isStockActive = computed(() =>
  ['/admin/stock-mdf', '/admin/stock-canto'].some(p => page.url.includes(p)));
const isAchatsActive = computed(() =>
  ['/admin/achats', '/admin/fournisseurs'].some(p => page.url.includes(p)));

const pageName = computed(() => ({
  '/admin/dashboard':        'Tableau de Bord',
  '/admin/invoices':         'Factures & Devis',
  '/admin/orders':           'Ventes (POS)',
  '/admin/stock-mdf':        'Stock MDF / LATI',
  '/admin/stock-canto':      'Stock Bandchant',
  '/admin/achats':           'Réception Achats',
  '/admin/achats-historique':'Historique des Achats',
  '/admin/fournisseurs':     'Fournisseurs & Dettes',
  '/admin/clients':          'Clients & Crédits',
  '/admin/employees':        'Équipe & Salaf',
  '/admin/attendance':       'Pointage Quotidien',
  '/admin/payroll':          'Paie Hebdomadaire',
  '/admin/charges':          'Charges & Dépenses',
  '/admin/services':         'Services & Tarifs',
  '/admin/settings':         'Paramètres',
  '/admin/statistiques':     'Statistiques',
  '/admin/system-logs':      'Audit & Activités',
  '/admin/users':            'Utilisateurs & Accès',
  '/admin/workshop-queue':   "Tableau de l'Atelier",
  '/admin/workshop-stats':   'Stats Atelier',
  '/admin/backups':          'Sauvegardes',
  '/admin/atelier':          'Atelier Mobile',
  '/admin/reports':          'Rapports PDF',
}[page.url] || 'Administration'));

// ─── Auto-open menus ─────────────────────────────────────────────
if (isHrActive.value)     hrMenuOpen.value = true;
if (isStockActive.value)  stockMenuOpen.value = true;
if (isAchatsActive.value) achatsMenuOpen.value = true;

// ─── Mobile close ────────────────────────────────────────────────
const closeMobileOnNav = (e) => {
  if (e.target.closest('a')) isMobileMenuOpen.value = false;
};

// ─── Notifications ───────────────────────────────────────────────
const fetchNotifications = async () => {
  try {
    const res = await axios.get('/api/admin/dashboard');
    const lowStock = res.data.low_stock_count || 0;
    notifications.value = [];
    if (lowStock > 0) {
      notifications.value.push({
        id: 1,
        title: 'Alerte Stock Bas',
        message: `${lowStock} produit(s) atteignent le seuil critique.`,
        icon: LayersIcon,
        colorClass: 'bg-amber-100 text-amber-600'
      });
    }
  } catch (e) { /* silent */ }
};

// Close notif on outside click
const handleClickOutside = (e) => {
  if (notificationRef.value && !notificationRef.value.contains(e.target)) {
    isNotificationsOpen.value = false;
  }
};

// ─── Logout ──────────────────────────────────────────────────────
const logout = () => {
  if (confirm('Voulez-vous vraiment vous déconnecter ?')) router.post('/logout');
};

// ─── Lifecycle ───────────────────────────────────────────────────
onMounted(() => {
  if (userRole.value === 'admin' || userRole.value === 'cashier') fetchNotifications();
  window.addEventListener('global-print', handleGlobalPrint);
  document.addEventListener('click', handleClickOutside);
});
onUnmounted(() => {
  window.removeEventListener('global-print', handleGlobalPrint);
  document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
.page-fade-enter-active, .page-fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.page-fade-enter-from, .page-fade-leave-to {
  opacity: 0;
  transform: translateY(4px);
}

/* Sidebar slide (mobile) */
.slide-enter-active, .slide-leave-active { transition: transform 0.3s cubic-bezier(0.4,0,0.2,1); }
.slide-enter-from, .slide-leave-to { transform: translateX(-100%); }

/* Overlay fade */
.fade-enter-active, .fade-leave-active { transition: opacity 0.25s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

/* Notification dropdown */
.fade-slide-enter-active, .fade-slide-leave-active { transition: all 0.2s cubic-bezier(0.4,0,0.2,1); }
.fade-slide-enter-from { opacity: 0; transform: translateY(-8px) scale(0.96); }
.fade-slide-leave-to   { opacity: 0; transform: translateY(-8px) scale(0.96); }


/* Subtle hover bg for dark sidebar */
.hover\:bg-white\/8:hover  { background-color: rgba(255,255,255,0.08); }
.hover\:bg-white\/6:hover  { background-color: rgba(255,255,255,0.06); }
.bg-white\/8  { background-color: rgba(255,255,255,0.08); }
.bg-white\/5  { background-color: rgba(255,255,255,0.05); }
.bg-white\/10 { background-color: rgba(255,255,255,0.10); }
</style>
