<template>
  <ToastNotification />
  <InvoiceTemplate v-if="printData" v-bind="printData" />
  <div class="flex h-screen bg-surface font-sans overflow-hidden shadow-none print:hidden">

    <!-- ===== MOBILE OVERLAY ===== -->
    <transition name="fade">
      <div v-if="isMobileMenuOpen" 
           class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm md:hidden"
           @click="isMobileMenuOpen = false">
      </div>
    </transition>

    <!-- ===== SIDEBAR ===== -->
    <transition name="slide">
      <div class="fixed inset-y-0 left-0 z-50 w-72 bg-[#0f172a] text-white flex flex-col shadow-2xl
                  md:relative md:translate-x-0 md:flex border-r border-slate-800/40"
           :class="isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'">

        <!-- Logo & Close btn (mobile) -->
        <div class="p-8 flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <div class="relative group">
              <div class="absolute -inset-1 bg-gradient-to-tr from-brand-600 to-indigo-600 rounded-2xl blur opacity-25 group-hover:opacity-75 transition duration-1000"></div>
              <div class="relative w-12 h-12 bg-white rounded-2xl flex items-center justify-center shadow-premium overflow-hidden p-1.5">
                 <img src="/assets/logo.png" alt="Logo" class="w-full h-full object-contain">
              </div>
            </div>
            <div>
              <h2 class="text-2xl font-black tracking-tighter text-white leading-none">{{ companyShortName }}</h2>
              <p class="text-[9px] font-black text-brand-500 uppercase tracking-[0.3em] mt-1">ERP PRO</p>
            </div>
          </div>
          <!-- Close button on mobile -->
          <button @click="isMobileMenuOpen = false" class="md:hidden p-2 text-slate-400 hover:text-white transition-colors rounded-xl hover:bg-slate-800">
            <XIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Nav Links -->
        <nav class="flex-1 px-4 py-8 space-y-2 overflow-y-auto custom-scrollbar" @click="closeMobileOnNav">
          <!-- Worker ONLY sees the mobile execution board -->
          <template v-if="userRole === 'worker'">
            <div class="pt-4 pb-2 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Atelier</div>
            <Link href="/admin/atelier" class="nav-link" :class="{ 'nav-link-active': $page.url === '/admin/atelier' }">
              <UserCheckIcon class="w-5 h-5 mr-3 text-emerald-400" /> Atelier (Mobile)
            </Link>
          </template>

          <!-- Admins & Cashiers see administrative tools -->
          <template v-if="userRole === 'admin' || userRole === 'cashier'">
            <Link href="/admin/dashboard" class="nav-link" :class="{ 'nav-link-active': $page.url === '/admin/dashboard' }">
              <LayoutGridIcon class="w-5 h-5 mr-3" /> Tableau de Bord
            </Link>
            
            <div class="pt-4 pb-2 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Opérations</div>
            <Link href="/admin/invoices" class="nav-link" :class="{ 'nav-link-active': $page.url === '/admin/invoices' }">
              <ReceiptIcon class="w-5 h-5 mr-3" /> Factures & Devis
            </Link>
            <Link href="/admin/orders" class="nav-link" :class="{ 'nav-link-active': $page.url === '/admin/orders' }">
              <FileTextIcon class="w-5 h-5 mr-3" /> Ventes (POS)
            </Link>
            <a href="/pos" class="nav-link text-amber-400 hover:text-amber-300">
              <PlusCircleIcon class="w-5 h-5 mr-3" /> Ouvrir la Caisse
            </a>
            <Link href="/admin/workshop-queue" class="nav-link" :class="{ 'nav-link-active': $page.url === '/admin/workshop-queue' }">
              <ClipboardListIcon class="w-5 h-5 mr-3 text-indigo-400" /> File d'attente (Admin)
            </Link>
            <Link href="/admin/atelier" class="nav-link" :class="{ 'nav-link-active': $page.url === '/admin/atelier' }">
              <UserCheckIcon class="w-5 h-5 mr-3 text-emerald-400" /> Atelier (Mobile)
            </Link>
            <Link href="/admin/workshop-stats" class="nav-link" :class="{ 'nav-link-active': $page.url === '/admin/workshop-stats' }">
              <BarChart3Icon class="w-5 h-5 mr-3 text-amber-400" /> Stats Atelier
            </Link>

            <template v-if="userRole === 'admin'">
              <div class="pt-4 pb-2 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Inventaire & Achats</div>
              
              <div class="space-y-1">
                <button @click="stockMenuOpen = !stockMenuOpen" class="w-full flex items-center justify-between px-4 py-3 text-slate-400 rounded-xl font-bold text-sm transition-all duration-200 hover:bg-slate-800 hover:text-white" :class="{ 'bg-slate-800 text-white': stockMenuOpen || isStockActive }">
                  <div class="flex items-center">
                    <LayersIcon class="w-5 h-5 mr-3" :class="{ 'text-brand-400': isStockActive }" />
                    <span :class="{ 'text-white': isStockActive }">Gestion de Stock</span>
                  </div>
                  <ChevronDownIcon class="w-4 h-4 transition-transform duration-300" :class="{ 'rotate-180': stockMenuOpen }" />
                </button>
                <div v-show="stockMenuOpen" class="pl-12 pr-2 py-2 space-y-1">
                  <Link href="/admin/stock-mdf" class="nav-sub-link" :class="{ 'nav-sub-link-active': $page.url === '/admin/stock-mdf' }">Stock MDF / LATI</Link>
                  <Link href="/admin/stock-canto" class="nav-sub-link" :class="{ 'nav-sub-link-active': $page.url === '/admin/stock-canto' }">Stock Bandchant</Link>
                </div>
              </div>

              <div class="space-y-1 mt-1">
                <button @click="achatsMenuOpen = !achatsMenuOpen" class="w-full flex items-center justify-between px-4 py-3 text-slate-400 rounded-xl font-bold text-sm transition-all duration-200 hover:bg-slate-800 hover:text-white" :class="{ 'bg-slate-800 text-white': achatsMenuOpen || isAchatsActive }">
                  <div class="flex items-center">
                    <TruckIcon class="w-5 h-5 mr-3" :class="{ 'text-brand-400': isAchatsActive }" />
                    <span :class="{ 'text-white': isAchatsActive }">Achats & Fournisseurs</span>
                  </div>
                  <ChevronDownIcon class="w-4 h-4 transition-transform duration-300" :class="{ 'rotate-180': achatsMenuOpen }" />
                </button>
                <div v-show="achatsMenuOpen" class="pl-12 pr-2 py-2 space-y-1">
                  <Link href="/admin/achats" class="nav-sub-link" :class="{ 'nav-sub-link-active': $page.url === '/admin/achats' }">Réception Achats</Link>
                  <Link href="/admin/achats-historique" class="nav-sub-link" :class="{ 'nav-sub-link-active': $page.url === '/admin/achats-historique' }">Historique des Achats</Link>
                  <Link href="/admin/fournisseurs" class="nav-sub-link" :class="{ 'nav-sub-link-active': $page.url === '/admin/fournisseurs' }">Fournisseurs & Dettes</Link>
                </div>
              </div>
            </template>

            <div class="pt-4 pb-2 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Relation Client</div>
            <Link href="/admin/clients" class="nav-link" :class="{ 'nav-link-active': $page.url === '/admin/clients' }">
              <UsersIcon class="w-5 h-5 mr-3" /> Clients & Crédits
            </Link>
            
            <template v-if="userRole === 'admin'">
              <div class="pt-4 pb-2 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Ressources Humaines</div>
              <div class="space-y-1">
                <button @click="hrMenuOpen = !hrMenuOpen" class="w-full flex items-center justify-between px-4 py-3 text-slate-400 rounded-xl font-bold text-sm transition-all duration-200 hover:bg-slate-800 hover:text-white" :class="{ 'bg-slate-800 text-white': hrMenuOpen || isHrActive }">
                  <div class="flex items-center">
                    <HardHatIcon class="w-5 h-5 mr-3" :class="{ 'text-brand-400': isHrActive }" />
                    <span :class="{ 'text-white': isHrActive }">Gestion du Personnel</span>
                  </div>
                  <ChevronDownIcon class="w-4 h-4 transition-transform duration-300" :class="{ 'rotate-180': hrMenuOpen }" />
                </button>
                <div v-show="hrMenuOpen" class="pl-12 pr-2 py-2 space-y-1">
                  <Link href="/admin/employees" class="nav-sub-link" :class="{ 'nav-sub-link-active': $page.url === '/admin/employees' }">Équipe & Salaf</Link>
                  <Link href="/admin/attendance" class="nav-sub-link" :class="{ 'nav-sub-link-active': $page.url === '/admin/attendance' }">Pointage Quotidien</Link>
                  <Link href="/admin/payroll" class="nav-sub-link" :class="{ 'nav-sub-link-active': $page.url === '/admin/payroll' }">Paie Hebdomadaire</Link>
                </div>
              </div>
              <Link href="/admin/charges" class="nav-link" :class="{ 'nav-link-active': $page.url === '/admin/charges' }">
                <ReceiptIcon class="w-5 h-5 mr-3" /> Charges & Dépenses
              </Link>

              <div class="pt-4 pb-2 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Configuration & Rapports</div>
              <Link href="/admin/services" class="nav-link" :class="{ 'nav-link-active': $page.url === '/admin/services' }">
                <SettingsIcon class="w-5 h-5 mr-3" /> Services & Tarifs
              </Link>
              <Link href="/admin/settings" class="nav-link" :class="{ 'nav-link-active': $page.url === '/admin/settings' }">
                <SlidersIcon class="w-5 h-5 mr-3" /> Paramètres
              </Link>
              <Link href="/admin/statistiques" class="nav-link" :class="{ 'nav-link-active': $page.url === '/admin/statistiques' }">
                <PieChartIcon class="w-5 h-5 mr-3" /> Statistiques & Arbah
              </Link>
              <Link href="/admin/reports" class="nav-link" :class="{ 'nav-link-active': $page.url === '/admin/reports' }">
                <FileTextIcon class="w-5 h-5 mr-3 text-violet-400" /> Rapports PDF
              </Link>
              <Link href="/admin/system-logs" class="nav-link" :class="{ 'nav-link-active': $page.url === '/admin/system-logs' }">
                <ActivityIcon class="w-5 h-5 mr-3" /> Audit & Activités
              </Link>
              <Link href="/admin/users" class="nav-link" :class="{ 'nav-link-active': $page.url === '/admin/users' }">
                <ShieldIcon class="w-5 h-5 mr-3" /> Utilisateurs & Accès
              </Link>
              <Link href="/admin/backups" class="nav-link" :class="{ 'nav-link-active': $page.url === '/admin/backups' }">
                <DatabaseIcon class="w-5 h-5 mr-3 text-emerald-400" /> Sauvegardes
              </Link>
            </template>
          </template>
        </nav>
        
        <!-- Footer User -->
        <div class="p-6 bg-[#020617]/50 border-t border-slate-800/50 flex items-center group cursor-pointer hover:bg-slate-800/30 transition-all">
           <div class="relative">
              <img class="h-11 w-11 rounded-2xl bg-slate-800 flex-shrink-0 border-2 border-slate-700/50 group-hover:border-brand-500 transition-colors" 
                   :src="'https://ui-avatars.com/api/?name=' + authUser.name + '&background=0D8ABC&color=fff'" alt="User">
              <span class="absolute -bottom-1 -right-1 w-4 h-4 bg-emerald-500 border-2 border-[#0f172a] rounded-full shadow-lg"></span>
           </div>
           <div class="ml-4 overflow-hidden flex-1">
             <p class="text-sm font-black text-white truncate group-hover:text-brand-400 transition-colors">{{ authUser.name }}</p>
             <p class="text-[10px] font-black text-slate-500 truncate uppercase tracking-widest">{{ authUser.role }}</p>
           </div>
           <ChevronRightIcon class="w-4 h-4 text-slate-600 group-hover:text-white transition-all transform group-hover:translate-x-1" />
        </div>
      </div>
    </transition>
    
    <!-- ===== MAIN CONTENT AREA ===== -->
    <div class="flex-1 flex flex-col overflow-hidden relative min-w-0">
      <!-- TopBar -->
      <header class="bg-white/70 backdrop-blur-2xl border-b border-slate-200/60 px-6 py-4 flex justify-between items-center z-20 sticky top-0">
        <!-- Hamburger (mobile only) -->
        <button @click="isMobileMenuOpen = true" 
                class="md:hidden p-3 bg-slate-100 text-slate-600 rounded-xl hover:bg-slate-200 transition-all active:scale-95">
          <MenuIcon class="w-5 h-5" />
        </button>

        <div class="flex flex-col">
           <h2 class="text-xl font-black text-slate-900 tracking-tight">{{ pageName }}</h2>
           <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest hidden sm:block">Tableau de Bord / {{ pageName }}</p>
        </div>
        
        <div class="flex items-center space-x-4">
           <!-- Notifications Dropdown -->
           <div class="relative" ref="notificationRef">
              <div @click="isNotificationsOpen = !isNotificationsOpen" 
                   class="bg-slate-100/50 p-3 rounded-2xl text-slate-500 hover:text-brand-600 hover:bg-white hover:shadow-premium transition-all cursor-pointer border border-transparent hover:border-slate-100 relative group">
                 <BellIcon class="w-5 h-5 transition-transform group-hover:rotate-12" />
                 <span v-if="notifications.length > 0" class="absolute top-2 right-2 w-2.5 h-2.5 bg-rose-500 border-2 border-white rounded-full animate-bounce"></span>
              </div>

              <!-- Dropdown Content -->
              <transition name="fade-slide">
                 <div v-if="isNotificationsOpen" class="absolute right-0 mt-4 w-80 bg-white rounded-[32px] shadow-2xl border border-slate-100 overflow-hidden z-50 animate-in fade-in zoom-in-95 duration-200">
                    <div class="p-6 border-b border-slate-50 flex justify-between items-center bg-slate-50/50">
                       <h4 class="text-sm font-black text-slate-900 uppercase tracking-widest">Alertes Système</h4>
                       <span v-if="notifications.length > 0" class="px-2 py-1 bg-brand-50 text-brand-600 text-[10px] font-black rounded-lg">{{ notifications.length }}</span>
                    </div>
                    <div class="max-h-[350px] overflow-y-auto custom-scrollbar">
                       <div v-for="note in notifications" :key="note.id" class="p-5 hover:bg-slate-50 border-b border-slate-50 transition-colors cursor-pointer group">
                          <div class="flex gap-4">
                             <div :class="note.colorClass" class="w-10 h-10 rounded-xl flex-shrink-0 flex items-center justify-center shadow-sm">
                                <component :is="note.icon" class="w-5 h-5" />
                             </div>
                             <div>
                                <p class="text-xs font-black text-slate-800 group-hover:text-brand-600 transition-colors">{{ note.title }}</p>
                                <p class="text-[10px] text-slate-500 mt-1 font-medium leading-relaxed">{{ note.message }}</p>
                             </div>
                          </div>
                       </div>
                       <div v-if="notifications.length === 0" class="p-12 text-center text-slate-300">
                          <BellIcon class="w-12 h-12 mx-auto mb-3 opacity-20" />
                          <p class="text-xs font-bold uppercase tracking-widest">Aucune notification</p>
                       </div>
                    </div>
                    <div v-if="notifications.length > 0" class="p-4 bg-slate-50 text-center border-t border-slate-100">
                       <button @click="notifications = []" class="text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-rose-500 transition-colors">Tout effacer</button>
                    </div>
                 </div>
              </transition>
           </div>
           <div class="h-8 w-px bg-slate-200 mx-2"></div>
           
           <button @click="logout" class="p-3 text-slate-400 hover:text-rose-500 transition-colors" title="Déconnexion">
              <LogOutIcon class="w-5 h-5" />
           </button>
        </div>
      </header>
      
      <!-- Page Content -->
      <main class="flex-1 overflow-x-hidden overflow-y-auto bg-surface p-4 md:p-6 lg:p-10">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { usePage, router } from '@inertiajs/vue3';
import InvoiceTemplate from '@/Components/Print/InvoiceTemplate.vue';

const printData = ref(null);

const handleGlobalPrint = (event) => {
  printData.value = event.detail;
  setTimeout(() => {
    window.print();
    // Clear after print to stop it showing on screen
    setTimeout(() => {
      printData.value = null;
    }, 500);
  }, 300);
};
import { 
  LayoutGridIcon, FileTextIcon, PlusCircleIcon, LayersIcon, 
  UsersIcon, SettingsIcon, BellIcon, HardHatIcon, TruckIcon, ReceiptIcon,
  PieChartIcon, SlidersIcon, ChevronDownIcon, ActivityIcon, ShieldIcon,
  MenuIcon, XIcon, LogOutIcon, ChevronRightIcon, ClipboardListIcon, UserCheckIcon, BarChart3Icon, DatabaseIcon
} from 'lucide-vue-next';

const page = usePage();
const hrMenuOpen = ref(false);
const stockMenuOpen = ref(false);
const achatsMenuOpen = ref(false);
const isMobileMenuOpen = ref(false);
const isNotificationsOpen = ref(false);
const notifications = ref([]);

const isHrActive = computed(() => {
  return page.url.includes('/admin/employees') || 
         page.url.includes('/admin/attendance') || 
         page.url.includes('/admin/payroll');
});

const isStockActive = computed(() => {
  return page.url.includes('/admin/stock-mdf') || 
         page.url.includes('/admin/stock-canto');
});

const isAchatsActive = computed(() => {
  return page.url.includes('/admin/achats') || 
         page.url.includes('/admin/fournisseurs');
});

const pageName = computed(() => {
  const mappings = {
    '/admin/dashboard': 'Tableau de Bord',
    '/admin/invoices': 'Factures & Devis',
    '/admin/orders': 'Ventes (POS)',
    '/admin/stock-mdf': 'Stock MDF / LATI',
    '/admin/stock-canto': 'Stock Bandchant',
    '/admin/achats': 'Réception Achats',
    '/admin/achats-historique': 'Historique des Achats',
    '/admin/fournisseurs': 'Fournisseurs & Dettes',
    '/admin/clients': 'Clients & Crédits',
    '/admin/employees': 'Équipe & Salaf',
    '/admin/attendance': 'Pointage Quotidien',
    '/admin/payroll': 'Paie Hebdomadaire',
    '/admin/charges': 'Charges & Dépenses',
    '/admin/services': 'Services & Tarifs',
    '/admin/settings': 'Paramètres',
    '/admin/statistiques': 'Statistiques & Arbah',
    '/admin/system-logs': 'Audit & Activités',
    '/admin/users': 'Utilisateurs & Accès',
    '/admin/workshop-queue': 'Tableau de l\'Atelier',
    '/admin/workshop-stats': 'Stats Atelier',
    '/admin/backups': 'Sauvegardes',
    '/admin/atelier': 'Atelier Mobile'
  };
  return mappings[page.url] || 'Administration';
});

// Auto-open menus if active route is inside them
if (isHrActive.value) hrMenuOpen.value = true;
if (isStockActive.value) stockMenuOpen.value = true;
if (isAchatsActive.value) achatsMenuOpen.value = true;

// Close mobile menu when a nav link is clicked
const closeMobileOnNav = (e) => {
  if (e.target.closest('a')) {
    isMobileMenuOpen.value = false;
  }
};

const authUser = computed(() => page.props.auth.user || { name: 'Utilisateur', role: 'cashier' });
const userRole = computed(() => authUser.value.role);
const companyShortName = computed(() => {
  const s = page.props.settings || {};
  return s.company_name || 'Mon Entreprise';
});

// Notifications logic (Fetch low stock etc)
const fetchNotifications = async () => {
  try {
    // Note: In a full Inertia app, you might share these via props too
    const res = await axios.get('/api/admin/dashboard'); 
    const lowStock = res.data.low_stock_count || 0;
    
    notifications.value = [];
    if (lowStock > 0) {
      notifications.value.push({
        id: 1,
        title: 'Alerte Stock Bas',
        message: `Il y a ${lowStock} produits qui atteignent le seuil critique.`,
        icon: LayersIcon,
        colorClass: 'bg-amber-100 text-amber-600'
      });
    }
  } catch (e) { console.error(e); }
};

const logout = () => {
  if (confirm('Voulez-vous vraiment vous déconnecter ?')) {
    router.post('/logout');
  }
};

onMounted(() => {
  if (userRole.value === 'admin' || userRole.value === 'cashier') {
    fetchNotifications();
  }
  window.addEventListener('global-print', handleGlobalPrint);
});

onUnmounted(() => {
  window.removeEventListener('global-print', handleGlobalPrint);
});
</script>

<style scoped>

.nav-link {
  display: flex;
  align-items: center;
  padding: 0.75rem 1rem;
  color: #94a3b8;
  border-radius: 0.75rem;
  font-weight: 700;
  font-size: 0.875rem;
  transition: all 200ms cubic-bezier(0.4, 0, 0.2, 1);
}
.nav-link:hover {
  background-color: #1e293b;
  color: #ffffff;
}
.nav-link-active {
  background-color: #0284c7;
  color: #ffffff;
  box-shadow: 0 10px 15px -3px rgba(12, 74, 110, 0.2), 0 4px 6px -4px rgba(12, 74, 110, 0.2);
}
.nav-sub-link {
  display: block;
  padding: 0.625rem 1rem;
  color: #94a3b8;
  border-radius: 0.75rem;
  font-weight: 700;
  font-size: 0.75rem;
  transition: all 200ms cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}
.nav-sub-link:hover {
  background-color: #1e293b;
  color: #ffffff;
}
.nav-sub-link::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 0.375rem;
  height: 0.375rem;
  border-radius: 9999px;
  background-color: #334155;
  transition: background-color 150ms;
}
.nav-sub-link:hover::before {
  background-color: #64748b;
}
.nav-sub-link-active {
  color: #ffffff;
  background-color: rgba(30, 41, 59, 0.5);
}
.nav-sub-link-active::before {
  background-color: #0ea5e9;
  box-shadow: 0 0 8px rgba(14, 165, 233, 0.5);
}

/* Sidebar slide transition (mobile) */
.slide-enter-active, .slide-leave-active {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-enter-from, .slide-leave-to {
  transform: translateX(-100%);
}

/* Overlay fade */
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

/* Page transitions */
.page-enter-active, .page-leave-active { transition: all 0.3s ease; }
.page-enter-from { opacity: 0; transform: translateY(10px); }
.page-leave-to { opacity: 0; transform: translateY(-10px); }

.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }

/* Notification Slide transition */
.fade-slide-enter-active, .fade-slide-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(-10px) scale(0.95);
}
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-10px) scale(0.95);
}
</style>
