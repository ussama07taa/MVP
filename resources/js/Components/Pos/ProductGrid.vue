<template>
  <div class="flex-1 overflow-y-auto custom-scrollbar bg-slate-50/50">

    <!-- Loading Skeletons -->
    <div v-if="isLoading" class="p-4 sm:p-6 space-y-8 animate-pulse">
      <div v-for="i in 2" :key="'sk'+i">
        <div class="h-4 w-36 bg-slate-200 rounded mb-4"></div>
        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
          <div v-for="j in 4" :key="'skc'+j" class="bg-white rounded-2xl border border-slate-100 p-4 h-28"></div>
        </div>
      </div>
    </div>

    <template v-else>
      <!-- Sticky Category Pills -->
      <div class="sticky top-0 z-10 bg-slate-50/95 backdrop-blur-lg px-4 sm:px-6 pt-3 pb-2.5 border-b border-slate-200/60 shadow-sm">
        <div class="flex gap-2 overflow-x-auto pb-0.5 no-scrollbar">
          <button v-for="cat in categories" :key="cat.id"
            @click="$emit('update:selectedCategory', cat.id)"
            :class="selectedCategory === cat.id
              ? 'bg-slate-900 text-white shadow-md'
              : 'bg-white text-slate-600 border border-slate-200 hover:border-slate-300'"
            class="px-4 py-2 rounded-xl text-[11px] font-black uppercase tracking-wider transition-all duration-200 whitespace-nowrap shrink-0 active:scale-95">
            {{ cat.label }}
          </button>
        </div>
      </div>

      <div class="p-4 sm:p-6 space-y-8">

        <!-- ── Services ── -->
        <section v-show="filteredServices.length > 0" class="fade-in">
          <div class="flex items-center gap-2.5 mb-3">
            <div class="w-6 h-6 rounded-lg bg-blue-50 text-blue-500 flex items-center justify-center border border-blue-100 shrink-0">
              <ScissorsIcon class="w-3 h-3" />
            </div>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Services de Coupe &amp; Pose</span>
            <div class="flex-1 h-px bg-slate-200/70"></div>
          </div>
          <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
            <button v-for="srv in filteredServices" :key="'s'+srv.id"
              v-show="!srv.name.toLowerCase().includes('pose')"
              @click="cartStore.addToCart(srv, 'service')"
              class="group relative bg-white rounded-2xl p-3.5 text-left border border-slate-200/70 hover:border-blue-300 hover:shadow-lg hover:shadow-blue-100/60 transition-all duration-200 hover:-translate-y-0.5 active:scale-[0.98] overflow-hidden">
              <div class="absolute inset-0 bg-gradient-to-br from-blue-500/0 to-blue-500/5 opacity-0 group-hover:opacity-100 transition-opacity rounded-2xl pointer-events-none"></div>
              <div class="flex items-start justify-between mb-2.5">
                <div class="w-8 h-8 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center shrink-0">
                  <ScissorsIcon class="w-4 h-4 text-blue-500" />
                </div>
                <span class="text-[9px] font-black text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded-md border border-blue-100">m</span>
              </div>
              <p class="font-black text-slate-800 text-[11px] uppercase tracking-tight leading-snug group-hover:text-blue-700 mb-2 line-clamp-2">{{ srv.name }}</p>
              <div class="flex items-center justify-between">
                <span class="text-[9px] text-slate-400 font-bold">Prix</span>
                <span class="text-sm font-black text-blue-600">{{ srv.unit_price }}<span class="text-[9px] text-blue-400 ml-0.5">DH/m</span></span>
              </div>
              <div class="absolute top-2 right-2 w-5 h-5 rounded-full bg-blue-500 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all scale-75 group-hover:scale-100 text-[10px] font-black pointer-events-none">+</div>
            </button>

            <!-- Pose Canto Seule special card -->
            <button @click="cartStore.addCustomLabor('Pose Canto (Fourniture Client)', 2.00, 'mètre')"
              class="group relative bg-amber-50/80 rounded-2xl p-3.5 text-left border border-amber-200 hover:border-amber-400 hover:shadow-lg hover:shadow-amber-100/60 transition-all duration-200 hover:-translate-y-0.5 active:scale-[0.98] overflow-hidden">
              <div class="flex items-start justify-between mb-2.5">
                <div class="w-8 h-8 rounded-xl bg-amber-100 border border-amber-200 flex items-center justify-center shrink-0">
                  <ScissorsIcon class="w-4 h-4 text-amber-500" />
                </div>
                <span class="text-[9px] font-black text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded-md border border-amber-100">m</span>
              </div>
              <p class="font-black text-slate-800 text-[11px] uppercase tracking-tight mb-0.5">Pose Canto Seule</p>
              <p class="text-[9px] font-black text-amber-600 mb-2">Fourniture Client</p>
              <div class="flex items-center justify-between">
                <span class="text-[9px] text-slate-400 font-bold">Prix</span>
                <span class="text-sm font-black text-amber-600">2.00<span class="text-[9px] text-amber-400 ml-0.5">DH/m</span></span>
              </div>
            </button>
          </div>
        </section>

        <!-- ── Panneaux ── -->
        <section v-show="filteredPanels.length > 0" class="fade-in">
          <div class="flex items-center gap-2.5 mb-3">
            <div class="w-6 h-6 rounded-lg bg-orange-50 text-orange-500 flex items-center justify-center border border-orange-100 shrink-0">
              <LayersIcon class="w-3 h-3" />
            </div>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Panneaux (MDF / LATI)</span>
            <div class="flex-1 h-px bg-slate-200/70"></div>
          </div>
          <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
            <button v-for="pnl in filteredPanels" :key="'p'+pnl.id"
              @click="cartStore.addToCart(pnl, 'panel')"
              class="group relative bg-white rounded-2xl p-3.5 text-left border border-slate-200/70 hover:border-orange-300 hover:shadow-lg hover:shadow-orange-100/60 transition-all duration-200 hover:-translate-y-0.5 active:scale-[0.98] overflow-hidden">
              <div class="absolute inset-0 bg-gradient-to-br from-orange-500/0 to-orange-500/5 opacity-0 group-hover:opacity-100 transition-opacity rounded-2xl pointer-events-none"></div>
              <div class="flex items-start justify-between mb-2.5">
                <div class="w-9 h-9 rounded-xl border-2 border-white shadow-md shrink-0 overflow-hidden" :style="getPanelTextureStyle(pnl.color_code, pnl.color_name || pnl.type)">
                  <div class="w-full h-full rounded-[10px] border border-black/5"></div>
                </div>
                <div class="flex flex-col items-end gap-1 ml-1">
                  <span class="text-[8px] font-black bg-slate-900 text-white px-1.5 py-0.5 rounded-md uppercase tracking-wide">{{ pnl.finish_type || 'STD' }}</span>
                  <span :class="pnl.quantity <= 2 ? 'bg-rose-100 text-rose-600 animate-pulse' : 'bg-slate-100 text-slate-500'" class="text-[8px] font-black px-1.5 py-0.5 rounded-md">Stk:{{ pnl.quantity }}</span>
                </div>
              </div>
              <p class="font-black text-slate-800 text-[11px] uppercase tracking-tight group-hover:text-orange-700 line-clamp-1 mb-0.5">{{ pnl.color_name || pnl.type }}</p>
              <p class="text-[9px] text-slate-400 font-bold mb-2">{{ pnl.color_code }} · {{ pnl.size_x }}×{{ pnl.size_y }} · {{ pnl.thickness }}mm</p>
              <div class="flex items-center justify-between">
                <span class="text-[8px] text-slate-400 bg-slate-50 px-1.5 py-0.5 rounded border border-slate-100 font-bold">{{ pnl.type }}</span>
                <span class="text-sm font-black text-orange-600">{{ pnl.base_price_sell }}<span class="text-[9px] text-orange-400 ml-0.5">DH</span></span>
              </div>
            </button>
          </div>
        </section>

        <!-- ── Bandchant ── -->
        <section v-show="filteredCantos.length > 0" class="fade-in">
          <div class="flex items-center gap-2.5 mb-3">
            <div class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-500 flex items-center justify-center border border-emerald-100 shrink-0">
              <PaletteIcon class="w-3 h-3" />
            </div>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Bandchant (Canto)</span>
            <div class="flex-1 h-px bg-slate-200/70"></div>
          </div>
          <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
            <button v-for="cnt in filteredCantos" :key="'c'+cnt.id"
              @click="cartStore.addToCart(cnt, 'canto')"
              class="group relative bg-white rounded-2xl p-3.5 text-left border border-slate-200/70 hover:border-emerald-300 hover:shadow-lg hover:shadow-emerald-100/60 transition-all duration-200 hover:-translate-y-0.5 active:scale-[0.98] overflow-hidden">
              <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/0 to-emerald-500/5 opacity-0 group-hover:opacity-100 transition-opacity rounded-2xl pointer-events-none"></div>
              <div class="flex items-start justify-between mb-2.5">
                <div class="w-9 h-9 rounded-xl border-2 border-white shadow-md shrink-0 flex items-center justify-center overflow-hidden" :style="{ background: getHexColor(cnt.color_code) }">
                  <div class="w-full h-full rounded-[10px] border border-black/5"></div>
                </div>
                <span class="text-[8px] font-black bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded-md border border-slate-200 ml-1">{{ cnt.finish_type || 'STD' }}</span>
              </div>
              <p class="font-black text-slate-800 text-[11px] uppercase tracking-tight group-hover:text-emerald-700 line-clamp-1 mb-0.5">{{ cnt.color_name || cnt.name || 'BANDCHANT' }}</p>
              <div class="flex items-center gap-1.5 mb-2">
                <span class="text-[9px] text-slate-400 font-bold">{{ cnt.color_code || 'N/A' }}</span>
                <span v-if="cnt.width_mm || cnt.thickness_mm" class="text-[8px] font-black bg-emerald-50 text-emerald-600 px-1.5 py-0.5 rounded border border-emerald-100">{{ cnt.width_mm }}×{{ cnt.thickness_mm }}mm</span>
              </div>
              <div class="flex items-center justify-between">
                <span :class="cnt.total_length_remaining <= 5 ? 'text-rose-600 bg-rose-50 border-rose-100' : 'text-emerald-600 bg-emerald-50 border-emerald-100'" class="text-[9px] font-black px-1.5 py-0.5 rounded border">
                  {{ Number(cnt.total_length_remaining).toFixed(1) }}m
                </span>
                <span class="text-sm font-black text-emerald-600">{{ cnt.base_price_sell_per_m }}<span class="text-[9px] text-emerald-400 ml-0.5">DH/m</span></span>
              </div>
            </button>
          </div>
        </section>

        <!-- ── Consommables ── -->
        <section v-show="filteredConsumables.length > 0" class="fade-in">
          <div class="flex items-center gap-2.5 mb-3">
            <div class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-500 flex items-center justify-center border border-indigo-100 shrink-0">
              <PackageIcon class="w-3 h-3" />
            </div>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Quincaillerie &amp; Consommables</span>
            <div class="flex-1 h-px bg-slate-200/70"></div>
          </div>
          <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
            <button v-for="con in filteredConsumables" :key="'con'+con.id"
              @click="cartStore.addToCart(con, 'consumable')"
              class="group relative bg-white rounded-2xl p-3.5 text-left border border-slate-200/70 hover:border-indigo-300 hover:shadow-lg hover:shadow-indigo-100/60 transition-all duration-200 hover:-translate-y-0.5 active:scale-[0.98] overflow-hidden">
              <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/0 to-indigo-500/5 opacity-0 group-hover:opacity-100 transition-opacity rounded-2xl pointer-events-none"></div>
              <div class="flex items-start justify-between mb-2.5">
                <div class="w-8 h-8 rounded-xl bg-indigo-50 flex items-center justify-center border border-indigo-100 shrink-0">
                  <PackageIcon class="w-4 h-4 text-indigo-500" />
                </div>
                <span :class="con.quantity_in_stock <= 5 ? 'text-rose-600 bg-rose-50 border-rose-100' : 'text-indigo-600 bg-indigo-50 border-indigo-100'"
                  class="text-[8px] font-black px-1.5 py-0.5 rounded-md border">
                  {{ con.quantity_in_stock }} {{ con.unit }}
                </span>
              </div>
              <p class="font-black text-slate-800 text-[11px] uppercase tracking-tight group-hover:text-indigo-700 line-clamp-2 mb-2">{{ con.name }}</p>
              <div class="flex items-center justify-between">
                <span class="text-[9px] text-slate-400 font-bold">En stock</span>
                <span class="text-sm font-black text-indigo-600">{{ con.base_price_sell }}<span class="text-[9px] text-indigo-400 ml-0.5">DH/{{ con.unit }}</span></span>
              </div>
            </button>
          </div>
        </section>

        <!-- Padding bottom for FAB on mobile -->
        <div class="h-20 lg:hidden"></div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useCartStore } from '@/stores/cart';
import { ScissorsIcon, LayersIcon, PaletteIcon, PackageIcon } from 'lucide-vue-next';
import { commonTextures } from '@/colors';

const props = defineProps({
  isLoading: Boolean,
  searchQuery: String,
  selectedCategory: String,
  services: { type: Array, default: () => [] },
  panels:   { type: Array, default: () => [] },
  cantos:   { type: Array, default: () => [] },
  consumables: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:selectedCategory']);
const cartStore = useCartStore();

const categories = [
  { id: 'all',        label: 'Tous'          },
  { id: 'panel',      label: 'Panneaux'      },
  { id: 'canto',      label: 'Bandchant'     },
  { id: 'service',    label: 'Services'      },
  { id: 'consumable', label: 'Quincaillerie' },
];

const filteredPanels = computed(() => {
  if (props.selectedCategory !== 'all' && props.selectedCategory !== 'panel') return [];
  const q = (props.searchQuery || '').toLowerCase();
  return (props.panels || []).filter(p =>
    (p.type || '').toLowerCase().includes(q) ||
    (p.color_name || '').toLowerCase().includes(q) ||
    (p.color_code || '').toLowerCase().includes(q)
  );
});

const filteredCantos = computed(() => {
  if (props.selectedCategory !== 'all' && props.selectedCategory !== 'canto') return [];
  const q = (props.searchQuery || '').toLowerCase();
  return (props.cantos || []).filter(c =>
    (c.name || '').toLowerCase().includes(q) ||
    (c.color_name || '').toLowerCase().includes(q) ||
    (c.color_code || '').toLowerCase().includes(q)
  );
});

const filteredServices = computed(() => {
  if (props.selectedCategory !== 'all' && props.selectedCategory !== 'service') return [];
  return (props.services || []).filter(s =>
    (s.name || '').toLowerCase().includes((props.searchQuery || '').toLowerCase())
  );
});

const filteredConsumables = computed(() => {
  if (props.selectedCategory !== 'all' && props.selectedCategory !== 'consumable') return [];
  return (props.consumables || []).filter(c =>
    (c.name || '').toLowerCase().includes((props.searchQuery || '').toLowerCase())
  );
});

const getHexColor = (code) => {
  if (!code) return '#f1f5f9';
  const c = code.toUpperCase();
  const texture = (commonTextures || []).find(t => t.code.toUpperCase() === c);
  if (texture?.hex) return texture.hex;
  if (c.includes('BLANC')) return '#f8fafc';
  if (c.includes('NOIR'))  return '#1e293b';
  if (c.includes('GRIS'))  return '#e2e8f0';
  let hash = 0;
  for (let i = 0; i < c.length; i++) hash = c.charCodeAt(i) + ((hash << 5) - hash);
  let color = '#';
  for (let i = 0; i < 3; i++) color += ('00' + ((hash >> (i * 8)) & 0xFF).toString(16)).substr(-2);
  return color;
};

const getPanelTextureStyle = (colorCode, colorName) => {
  if (colorCode) {
    const texture = (commonTextures || []).find(t => t.code.toUpperCase() === (colorCode || '').toUpperCase());
    if (texture?.hex) return { background: texture.hex };
  }
  const name = (colorName || '').toLowerCase();
  if (name.includes('chêne') || name.includes('oak'))   return { background: 'linear-gradient(135deg,#eab308,#b45309)' };
  if (name.includes('noyer') || name.includes('walnut')) return { background: 'linear-gradient(135deg,#78350f,#451a03)' };
  if (name.includes('blanc') || name.includes('white'))  return { background: 'linear-gradient(135deg,#f8fafc,#cbd5e1)' };
  if (colorCode) {
    const hex = getHexColor(colorCode);
    if (hex !== '#f1f5f9') return { background: hex };
  }
  return { background: 'linear-gradient(135deg,#f59e0b,#d97706)' };
};
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 8px; }

.fade-in { animation: fadeIn 0.4s ease-out both; }
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(6px); }
  to   { opacity: 1; transform: translateY(0);   }
}
</style>
