<template>
  <Teleport to="body">
    <transition name="fade">
      <div v-if="isOpen" class="fixed inset-0 z-[10000] flex items-start justify-center p-4 pt-[15vh] bg-slate-950/40 backdrop-blur-sm" @click.self="close">
        <transition name="pop">
          <div v-if="isOpen" class="w-full max-w-2xl bg-white rounded-[2rem] shadow-premium overflow-hidden border border-slate-200">
            <!-- Search Input -->
            <div class="relative p-6 border-b border-slate-100 flex items-center gap-4">
              <SearchIcon class="w-6 h-6 text-brand-500" />
              <input ref="searchInput" v-model="query" type="text" 
                     placeholder="Rechercher une page (ex: clients, stock...)" 
                     class="flex-1 bg-transparent border-none text-lg font-semibold text-slate-900 focus:ring-0 placeholder:text-slate-400"
                     @keydown.esc="close"
                     @keydown.down="move(1)"
                     @keydown.up="move(-1)"
                     @keydown.enter="selectActive" />
              <div class="flex items-center gap-1.5 px-2 py-1 bg-slate-100 rounded-lg border border-slate-200">
                <span class="text-[10px] font-black text-slate-500 uppercase">ESC</span>
              </div>
            </div>

            <!-- Results -->
            <div class="max-h-[60vh] overflow-y-auto p-3 custom-scrollbar">
              <div v-if="filteredResults.length === 0" class="py-12 text-center text-slate-400">
                <SearchIcon class="w-12 h-12 mx-auto mb-3 opacity-20" />
                <p class="text-sm font-bold">Aucune page trouvée pour "{{ query }}"</p>
              </div>

              <div v-for="(res, idx) in filteredResults" :key="res.href"
                   @mouseenter="activeIndex = idx"
                   @click="selectResult(res)"
                   :class="activeIndex === idx ? 'bg-brand-50 border-brand-100' : 'bg-transparent border-transparent'"
                   class="group flex items-center justify-between p-4 rounded-2xl border transition-all cursor-pointer">
                <div class="flex items-center gap-4">
                  <div :class="activeIndex === idx ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-500'" 
                       class="w-10 h-10 rounded-xl flex items-center justify-center transition-colors">
                    <component :is="res.icon" class="w-5 h-5" />
                  </div>
                  <div>
                    <h4 class="text-sm font-bold" :class="activeIndex === idx ? 'text-brand-900' : 'text-slate-700'">{{ res.label }}</h4>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">{{ res.category }}</p>
                  </div>
                </div>
                <ChevronRightIcon v-if="activeIndex === idx" class="w-4 h-4 text-brand-500" />
              </div>
            </div>

            <!-- Footer Hints -->
            <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-center gap-6">
              <div class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase">
                <span class="p-1 px-1.5 bg-white border border-slate-200 rounded shadow-sm text-slate-600">Enter</span>
                <span>Sélectionner</span>
              </div>
              <div class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase">
                <span class="p-1 px-1.5 bg-white border border-slate-200 rounded shadow-sm text-slate-600">↑↓</span>
                <span>Naviguer</span>
              </div>
            </div>
          </div>
        </transition>
      </div>
    </transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { 
  SearchIcon, ChevronRightIcon, LayoutGridIcon, ReceiptIcon, 
  FileTextIcon, UsersIcon, PackageIcon, TruckIcon, HardHatIcon, 
  SettingsIcon, SlidersIcon, ActivityIcon, ShieldIcon, DatabaseIcon 
} from 'lucide-vue-next';

const isOpen = ref(false);
const query = ref('');
const searchInput = ref(null);
const activeIndex = ref(0);

const pages = [
  { label: 'Tableau de Bord', href: '/admin/dashboard', icon: LayoutGridIcon, category: 'Général' },
  { label: 'Factures & Devis', href: '/admin/invoices', icon: ReceiptIcon, category: 'Ventes' },
  { label: 'Ventes (POS)', href: '/admin/orders', icon: FileTextIcon, category: 'Ventes' },
  { label: 'Clients & Crédits', href: '/admin/clients', icon: UsersIcon, category: 'CRM' },
  { label: 'Stock MDF / LATI', href: '/admin/stock-mdf', icon: PackageIcon, category: 'Inventaire' },
  { label: 'Stock Bandchant', href: '/admin/stock-canto', icon: PackageIcon, category: 'Inventaire' },
  { label: 'Réception Achats', href: '/admin/achats', icon: TruckIcon, category: 'Achats' },
  { label: 'Fournisseurs & Dettes', href: '/admin/fournisseurs', icon: TruckIcon, category: 'Achats' },
  { label: 'Personnel & Équipe', href: '/admin/employees', icon: HardHatIcon, category: 'RH' },
  { label: 'Pointage Quotidien', href: '/admin/attendance', icon: HardHatIcon, category: 'RH' },
  { label: 'Charges & Dépenses', href: '/admin/charges', icon: ReceiptIcon, category: 'RH' },
  { label: 'Services & Tarifs', href: '/admin/services', icon: SettingsIcon, category: 'Configuration' },
  { label: 'Paramètres Enterprise', href: '/admin/settings', icon: SlidersIcon, category: 'Configuration' },
  { label: 'Audit & Logs', href: '/admin/system-logs', icon: ActivityIcon, category: 'Configuration' },
  { label: 'Sauvegardes', href: '/admin/backups', icon: DatabaseIcon, category: 'Configuration' },
];

const actions = [
  { label: 'Nouveau Client', icon: UsersIcon, category: 'Action Rapide', action: () => router.visit('/admin/clients?new=true') },
  { label: 'Nouvelle Facture', icon: ReceiptIcon, category: 'Action Rapide', action: () => router.visit('/admin/invoices?new=true') },
  { label: 'Ouvrir la Caisse', icon: FileTextIcon, category: 'Action Rapide', action: () => router.visit('/pos') },
];

const filteredResults = computed(() => {
  const q = query.value.toLowerCase();
  
  const filteredPages = pages.filter(p => 
    p.label.toLowerCase().includes(q) || 
    p.category.toLowerCase().includes(q)
  );

  const filteredActions = actions.filter(a => 
    a.label.toLowerCase().includes(q)
  );

  return [...filteredActions, ...filteredPages];
});

const open = () => {
  isOpen.value = true;
  query.value = '';
  activeIndex.value = 0;
  nextTick(() => searchInput.value?.focus());
};

const close = () => {
  isOpen.value = false;
};

const move = (dir) => {
  activeIndex.value = (activeIndex.value + dir + filteredResults.value.length) % filteredResults.value.length;
};

const selectResult = (res) => {
  if (res.action) {
    res.action();
  } else {
    router.visit(res.href);
  }
  close();
};

const selectActive = () => {
  if (filteredResults.value[activeIndex.value]) {
    selectResult(filteredResults.value[activeIndex.value]);
  }
};

const handleKeydown = (e) => {
  if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
    e.preventDefault();
    open();
  }
};

onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleKeydown));

defineExpose({ open });
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.pop-enter-active { transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
.pop-leave-active { transition: all 0.2s ease-in; }
.pop-enter-from { opacity: 0; transform: scale(0.95) translateY(-20px); }
.pop-leave-to { opacity: 0; transform: scale(0.95); }
</style>
