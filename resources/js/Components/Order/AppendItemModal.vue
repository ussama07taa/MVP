<template>
  <TransitionRoot as="template" :show="show">
    <Dialog as="div" class="relative z-[150]" @close="$emit('close')">
      <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-md transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
            <DialogPanel class="relative transform overflow-hidden rounded-[2.5rem] bg-[#FAFAF9] text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-6xl flex flex-col h-[85vh] border border-white/20">
              
              <!-- Header -->
              <div class="shrink-0 bg-white border-b border-slate-200/60 px-8 py-6 flex items-center justify-between">
                <div>
                  <h3 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                    <span class="w-10 h-10 rounded-2xl bg-amber-500 flex items-center justify-center shadow-lg shadow-amber-500/20">
                      <PlusIcon class="w-5 h-5 text-slate-950" />
                    </span>
                    Ajouter des articles à la Facture #{{ order?.display_reference }}
                  </h3>
                  <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.25em] mt-1.5 ml-1">Client: {{ order?.client?.name }}</p>
                </div>
                <button @click="$emit('close')" class="w-12 h-12 flex items-center justify-center bg-slate-100 rounded-2xl text-slate-400 hover:text-slate-950 hover:bg-slate-200 transition-all active:scale-95">
                  <XIcon class="w-6 h-6" />
                </button>
              </div>

              <div class="flex-1 flex overflow-hidden">
                <!-- Left: Product Picker (Simplified Grid) -->
                <div class="flex-1 flex flex-col min-w-0 bg-slate-50/30">
                  <!-- Search & Categories -->
                  <div class="px-8 py-4 border-b border-slate-200/40 bg-white/50 backdrop-blur-sm sticky top-0 z-10 flex gap-4 items-center">
                    <div class="flex-1 relative group">
                      <SearchIcon class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-amber-600" />
                      <input type="text" v-model="searchQuery" placeholder="Rechercher panneau, chant ou service..." 
                        class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-2xl focus:outline-none focus:ring-4 focus:ring-amber-500/5 focus:border-amber-500/30 font-bold text-sm transition-all">
                    </div>
                    <div class="flex gap-2">
                       <button v-for="cat in ['all', 'panel', 'canto', 'service', 'consumable']" :key="cat"
                        @click="selectedCategory = cat"
                        :class="selectedCategory === cat ? 'bg-slate-900 text-amber-500' : 'bg-white text-slate-500 border border-slate-200 hover:border-slate-300'"
                        class="px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">
                        {{ cat === 'all' ? 'Tous' : (cat === 'panel' ? 'Panneaux' : (cat === 'canto' ? 'Chants' : (cat === 'service' ? 'Services' : 'Quincaillerie'))) }}
                       </button>
                    </div>
                  </div>

                  <!-- Products Results -->
                  <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                      <!-- Simplified Product Cards (Mapping from ProductGrid) -->
                      <button v-for="item in filteredItems" :key="item.id + item.type"
                        @click="addToAppendCart(item)"
                        class="group bg-white rounded-3xl p-4 text-left border border-slate-200/60 hover:border-amber-500/50 hover:shadow-xl transition-all duration-300 flex flex-col h-full active:scale-95">
                        <div class="flex justify-between items-start mb-3">
                          <div class="w-10 h-10 rounded-xl flex items-center justify-center" :class="item.iconBg">
                             <component :is="item.icon" class="w-5 h-5 text-inherit" />
                          </div>
                          <span class="text-[9px] font-black px-2 py-1 bg-slate-50 border border-slate-100 rounded-lg text-slate-400">{{ item.price }} DH</span>
                        </div>
                        <p class="font-black text-slate-900 text-[11px] uppercase tracking-tight line-clamp-2 leading-none mb-2">{{ item.name }}</p>
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-auto">{{ item.batchInfo }}</p>
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Right: Append Cart & Summary -->
                <div class="w-80 border-l border-slate-200/60 flex flex-col bg-white">
                  <div class="p-6 border-b border-slate-100">
                    <h4 class="text-sm font-black text-slate-900 uppercase tracking-[0.15em]">Articles à ajouter</h4>
                  </div>

                  <div class="flex-1 overflow-y-auto p-6 space-y-4 custom-scrollbar">
                    <div v-if="appendCart.length === 0" class="h-full flex flex-col items-center justify-center text-slate-300 text-center py-10">
                      <PlusIcon class="w-10 h-10 mb-4 opacity-20" />
                      <p class="text-xs font-bold uppercase tracking-widest">Le panier est vide</p>
                    </div>
                    
                    <TransitionGroup name="list">
                      <div v-for="(item, idx) in appendCart" :key="idx" class="bg-slate-50/50 border border-slate-100 rounded-2xl p-4 relative group/item">
                        <button @click="appendCart.splice(idx, 1)" class="absolute -right-2 -top-2 w-7 h-7 bg-white border border-slate-200 rounded-full flex items-center justify-center text-rose-500 shadow-sm opacity-0 group-hover/item:opacity-100 transition-opacity">
                          <Trash2Icon class="w-3.5 h-3.5" />
                        </button>
                        <p class="font-black text-slate-900 text-[10px] uppercase line-clamp-2 mb-2 pr-4">{{ item.name }}</p>
                        <div class="flex items-center justify-between">
                           <div class="flex items-center gap-2">
                             <input type="number" v-model="item.quantity" min="0.1" step="0.1" class="w-14 h-8 bg-white border border-slate-200 rounded-lg text-center font-black text-xs p-0 focus:ring-0 focus:border-amber-500">
                             <span class="text-[9px] font-bold text-slate-400 uppercase">{{ item.unit }}</span>
                           </div>
                           <span class="font-black text-slate-950 text-xs">{{ (item.quantity * item.unit_price).toFixed(2) }} DH</span>
                        </div>

                        <!-- Collage toggle (same as POS) -->
                        <div v-if="item.type === 'canto'" class="mt-3 pt-3 border-t border-slate-100">
                          <label class="flex items-center gap-2 cursor-pointer group/toggle">
                            <div class="relative w-8 h-4 bg-slate-200 rounded-full transition-colors group-has-[:checked]:bg-amber-500">
                              <input type="checkbox" v-model="item.with_canto_service" @change="updateCantoPrices(item)" class="sr-only">
                              <div class="absolute left-0.5 top-0.5 w-3 h-3 bg-white rounded-full transition-transform group-has-[:checked]:translate-x-4"></div>
                            </div>
                            <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">Collage de chant</span>
                          </label>
                          <div v-if="item.with_canto_service" class="mt-2 flex items-center justify-between bg-white px-3 py-2 rounded-xl border border-slate-100">
                            <span class="text-[8px] font-bold text-slate-400 uppercase tracking-widest">Tarif (DH/m)</span>
                            <input type="number" v-model.number="item.custom_canto_service_price"
                              @input="updateCantoPrices(item)" min="0" step="0.5"
                              class="w-12 text-right bg-transparent border-none text-slate-900 font-black text-[10px] focus:ring-0 p-0">
                          </div>
                        </div>
                      </div>
                    </TransitionGroup>
                  </div>

                  <div class="p-8 bg-slate-900 text-white rounded-t-[2.5rem] shadow-[-10px_-10px_40px_rgba(0,0,0,0.1)]">
                    <div class="flex justify-between items-baseline mb-6">
                      <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">Supplément Total</span>
                      <span class="text-2xl font-black text-amber-500">{{ cartTotal.toFixed(2) }} DH</span>
                    </div>

                    <button @click="submitAppend" :disabled="appendCart.length === 0 || isSubmitting"
                      class="w-full bg-amber-500 text-slate-900 font-black py-4 rounded-2xl hover:bg-amber-400 disabled:opacity-20 disabled:grayscale transition-all flex items-center justify-center gap-2 shadow-xl shadow-amber-500/20 active:scale-95">
                      <Loader2Icon v-if="isSubmitting" class="w-5 h-5 animate-spin" />
                      <CheckCircleIcon v-else class="w-5 h-5" />
                      CONFIRMER L'AJOUT
                    </button>
                  </div>
                </div>
              </div>

            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import { Dialog, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue';
import { PlusIcon, XIcon, SearchIcon, ScissorsIcon, LayersIcon, PaletteIcon, PackageIcon, Trash2Icon, CheckCircleIcon, Loader2Icon } from 'lucide-vue-next';
import { useToast } from '@/composables/useToast';

const props = defineProps({
  show: Boolean,
  order: Object,
  panels: Array,
  cantos: Array,
  services: Array,
  consumables: Array
});

const emit = defineEmits(['close', 'success']);
const toast = useToast();

const searchQuery = ref('');
const selectedCategory = ref('all');
const appendCart = ref([]);
const isSubmitting = ref(false);

const cartTotal = computed(() => appendCart.value.reduce((t, i) => t + (i.quantity * i.unit_price), 0));

const filteredItems = computed(() => {
  let results = [];
  const q = searchQuery.value.toLowerCase();

  // Panels
  if (selectedCategory.value === 'all' || selectedCategory.value === 'panel') {
    (props.panels || []).forEach(p => {
      if (!q || p.type.toLowerCase().includes(q) || (p.color_name && p.color_name.toLowerCase().includes(q))) {
        results.push({
          type: 'panel',
          id: p.id,
          name: `${p.type} ${p.color_name || ''} ${p.size_x}x${p.size_y}`,
          price: p.base_price_sell,
          unit_price: p.base_price_sell,
          unit: 'pcs',
          icon: LayersIcon,
          iconBg: 'bg-amber-50 text-amber-500',
          batchInfo: `Stk: ${p.quantity} | ${p.thickness}mm`
        });
      }
    });
  }

  // Cantos
  if (selectedCategory.value === 'all' || selectedCategory.value === 'canto') {
    (props.cantos || []).forEach(c => {
      if (!q || (c.color_name && c.color_name.toLowerCase().includes(q)) || (c.color_code && c.color_code.toLowerCase().includes(q))) {
        const cantoName = `CHANT ${c.color_name || c.color_code} [${c.finish_type || 'STD'}]`;
        results.push({
          type: 'canto',
          id: c.id,
          name: cantoName,
          base_name: cantoName,
          base_canto_price: Number(c.base_price_sell_per_m),
          price: c.base_price_sell_per_m,
          unit_price: c.base_price_sell_per_m,
          with_canto_service: false,
          custom_canto_service_price: 0,
          width_mm: c.width_mm,
          thickness_mm: c.thickness_mm,
          unit: 'm',
          icon: PaletteIcon,
          iconBg: 'bg-amber-100 text-amber-700',
          batchInfo: `Stk: ${Number(c.total_length_remaining).toFixed(1)}m | ${c.width_mm}mm`
        });
      }
    });
  }

  // Services
  if (selectedCategory.value === 'all' || selectedCategory.value === 'service') {
    (props.services || []).forEach(s => {
      if (!q || (s.name && s.name.toLowerCase().includes(q))) {
        results.push({
          type: 'service',
          id: s.id,
          name: s.name,
          price: s.unit_price,
          unit_price: s.unit_price,
          unit: 'u',
          icon: ScissorsIcon,
          iconBg: 'bg-slate-900 text-amber-500',
          batchInfo: 'Service Fixe'
        });
      }
    });
  }

  // Consumables
  if (selectedCategory.value === 'all' || selectedCategory.value === 'consumable') {
    (props.consumables || []).forEach(c => {
      if (!q || c.name.toLowerCase().includes(q)) {
        results.push({
          type: 'consumable',
          id: c.id,
          name: c.name,
          price: c.base_price_sell,
          unit_price: c.base_price_sell,
          unit: c.unit || 'u',
          icon: PackageIcon,
          iconBg: 'bg-slate-100 text-slate-500',
          batchInfo: `Stk: ${c.quantity_in_stock}`
        });
      }
    });
  }

  return results;
});

const recalculateCantoPriceAndName = (item) => {
  if (item.type !== 'canto') return;

  let price = Number(item.base_canto_price ?? item.unit_price);
  const prefixes = [];

  if (item.with_canto_service) {
    price += Number(item.custom_canto_service_price || 0);
    prefixes.push('Collage');
  }

  item.unit_price = price;
  item.name = prefixes.length > 0
    ? `[${prefixes.join(' + ')}] ${item.base_name}`
    : item.base_name;
};

const updateCantoPrices = (item) => {
  if (item.type !== 'canto') return;

  if (item.base_canto_price === undefined || item.base_canto_price === null) {
    item.base_canto_price = Number(item.unit_price);
  }
  if (!item.base_name) {
    item.base_name = item.name.replace(/\[.*?\]\s*/g, '').trim();
  }
  if (item.with_canto_service && !item.custom_canto_service_price) {
    item.custom_canto_service_price = 2;
  }

  recalculateCantoPriceAndName(item);
};

const addToAppendCart = (item) => {
  const existing = appendCart.value.find(i =>
    i.id === item.id &&
    i.type === item.type &&
    !!i.with_canto_service === !!item.with_canto_service
  );
  if (existing) {
    existing.quantity = Number(existing.quantity) + 1;
  } else {
    appendCart.value.push({
      ...item,
      quantity: 1,
      base_name: item.base_name || item.name,
      base_canto_price: item.type === 'canto' ? Number(item.base_canto_price ?? item.unit_price) : null,
      with_canto_service: false,
      custom_canto_service_price: 0,
    });
  }
};

const submitAppend = async () => {
  if (appendCart.value.length === 0) return;
  if (!props.order?.id) {
    toast.error("Erreur: Facture non identifiée.");
    return;
  }
  isSubmitting.value = true;
  try {
    const payload = {
      items: appendCart.value.map(i => ({
        type: i.type,
        id: i.id,
        quantity: i.quantity,
        unit_price: i.unit_price,
        name: i.name,
        with_canto_service: i.with_canto_service || false,
        custom_canto_service_price: i.custom_canto_service_price || 0,
        base_canto_price: i.base_canto_price || 0,
        base_name: i.base_name,
        width_mm: i.width_mm,
        thickness_mm: i.thickness_mm,
      }))
    };
    const res = await axios.post(`/api/admin/orders/${props.order.id}/append`, payload);
    toast.success('Articles ajoutés avec succès !');
    appendCart.value = [];
    emit('success');
    emit('close');
  } catch (error) {
    console.error('Append Error:', error);
    toast.error(error.response?.data?.error || error.message || 'Erreur lors de l\'ajout des articles.');
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 8px; }

.list-enter-active, .list-leave-active { transition: all 0.3s ease; }
.list-enter-from, .list-leave-to { opacity: 0; transform: translateX(20px); }
</style>
