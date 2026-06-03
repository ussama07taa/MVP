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
      <div class="sticky top-0 z-10 bg-[#FAFAF9]/80 backdrop-blur-xl px-4 sm:px-8 pt-4 pb-3 border-b border-slate-200/40">
        <div class="flex gap-3 overflow-x-auto pb-1 no-scrollbar">
          <button v-for="cat in categories" :key="cat.id"
            @click="$emit('update:selectedCategory', cat.id)"
            :class="selectedCategory === cat.id
              ? 'bg-slate-950 text-amber-500 shadow-xl shadow-slate-900/20 scale-105'
              : 'bg-white text-slate-500 border border-slate-200 hover:border-slate-400 hover:text-slate-900 shadow-sm'"
            class="px-6 py-2.5 rounded-2xl text-[11px] font-black uppercase tracking-[0.15em] transition-all duration-300 whitespace-nowrap shrink-0 active:scale-95">
            {{ cat.label }}
          </button>
        </div>
      </div>

      <div class="p-4 sm:p-8 space-y-12">

        <!-- ── Services ── -->
        <section v-show="filteredServices.length > 0" class="fade-in">
          <div class="flex items-center gap-3 mb-5 px-1">
            <div class="w-10 h-10 rounded-2xl bg-slate-950 text-amber-500 flex items-center justify-center shadow-lg shrink-0">
              <ScissorsIcon class="w-5 h-5" />
            </div>
            <div>
              <span class="text-xs font-black text-slate-900 uppercase tracking-widest block leading-none">Prestations de Service</span>
              <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1 block">Découpe, Placage & Usinage</span>
            </div>
            <div class="flex-1 h-px bg-slate-200/60 ml-4"></div>
          </div>
          <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
            <button v-for="srv in filteredServices" :key="'s'+srv.id"
              v-show="!srv.name.toLowerCase().includes('pose')"
              @click="cartStore.addToCart(srv, 'service')"
              class="group relative bg-white rounded-3xl p-5 text-left border border-slate-200/60 hover:border-amber-500/50 hover:shadow-xl transition-all duration-500 hover:-translate-y-1 active:scale-[0.98] overflow-hidden flex flex-col justify-between h-full min-h-[140px]">
              <div class="absolute inset-0 bg-gradient-to-br from-amber-500/0 via-amber-500/0 to-amber-500/5 opacity-0 group-hover:opacity-100 transition-opacity rounded-3xl pointer-events-none"></div>
              
              <div class="flex items-start justify-between mb-4">
                <div class="w-10 h-10 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center shrink-0 group-hover:bg-amber-500 group-hover:text-slate-950 transition-colors duration-300">
                  <ScissorsIcon class="w-5 h-5 text-slate-400 group-hover:text-inherit" />
                </div>
                <span class="text-[10px] font-black text-slate-950 bg-amber-500 px-2 py-1 rounded-lg shadow-sm">FIXE</span>
              </div>
              
              <div>
                <p class="font-black text-slate-900 text-xs uppercase tracking-tight leading-snug group-hover:text-amber-700 transition-colors line-clamp-2">{{ srv.name }}</p>
                <div class="mt-4 flex items-end justify-between">
                  <div class="flex flex-col">
                    <span class="text-[9px] text-slate-400 font-black uppercase tracking-widest mb-0.5">Tarif</span>
                    <span class="text-base font-black text-slate-950">{{ srv.unit_price }}<span class="text-[10px] text-slate-400 ml-1">DH/m</span></span>
                  </div>
                  <div class="w-8 h-8 rounded-full bg-slate-900 text-amber-500 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all translate-x-2 group-hover:translate-x-0 duration-300 shadow-lg">
                    <PlusIcon class="w-4 h-4" />
                  </div>
                </div>
              </div>
            </button>

            <!-- Pose Canto Seule special card -->
            <button @click="cartStore.addCustomLabor('Pose Canto (Fourniture Client)', 2.00, 'mètre')"
              class="group relative bg-slate-900 rounded-3xl p-5 text-left border border-slate-800 hover:border-amber-500 hover:shadow-xl transition-all duration-500 hover:-translate-y-1 active:scale-[0.98] overflow-hidden flex flex-col justify-between h-full min-h-[140px]">
              <div class="absolute inset-0 bg-gradient-to-br from-amber-500/10 to-transparent opacity-50"></div>
              
              <div class="flex items-start justify-between mb-4 relative z-10">
                <div class="w-10 h-10 rounded-2xl bg-amber-500 flex items-center justify-center shrink-0 shadow-lg shadow-amber-500/20">
                  <ScissorsIcon class="w-5 h-5 text-slate-950" />
                </div>
                <span class="text-[10px] font-black text-amber-500 border border-amber-500/30 px-2 py-1 rounded-lg">EXTERNE</span>
              </div>
              
              <div class="relative z-10">
                <p class="font-black text-white text-xs uppercase tracking-tight mb-0.5">Pose de Chant seul</p>
                <p class="text-[9px] font-black text-amber-500/80 uppercase tracking-widest mb-4 italic">Fourniture Client</p>
                
                <div class="flex items-end justify-between">
                  <div class="flex flex-col">
                    <span class="text-[9px] text-slate-500 font-black uppercase tracking-widest mb-0.5">Tarif</span>
                    <span class="text-base font-black text-amber-500">2.00<span class="text-[10px] text-amber-600/50 ml-1">DH/m</span></span>
                  </div>
                  <div class="w-8 h-8 rounded-full bg-amber-500 text-slate-950 flex items-center justify-center shadow-lg">
                    <PlusIcon class="w-4 h-4" />
                  </div>
                </div>
              </div>
            </button>
          </div>
        </section>

        <!-- ── Panneaux ── -->
        <section v-show="filteredPanels.length > 0" class="fade-in">
          <div class="flex items-center gap-3 mb-5 px-1">
            <div class="w-10 h-10 rounded-2xl bg-amber-500 text-slate-950 flex items-center justify-center shadow-lg shrink-0">
              <LayersIcon class="w-5 h-5" />
            </div>
            <div>
              <span class="text-xs font-black text-slate-900 uppercase tracking-widest block leading-none">Panneaux & Décor</span>
              <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1 block">MDF, Lati & Stratifié</span>
            </div>
            <div class="flex-1 h-px bg-slate-200/60 ml-4"></div>
          </div>
          <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
            <button v-for="pnl in filteredPanels" :key="'p'+pnl.id"
              @click="cartStore.addToCart(pnl, 'panel')"
              class="group relative bg-white rounded-3xl p-4 text-left border border-slate-200/60 hover:border-amber-500/50 hover:shadow-[0_20px_50px_rgba(202,138,4,0.1)] transition-all duration-500 hover:-translate-y-1 active:scale-[0.98] overflow-hidden flex flex-col h-full">
              
              <div class="flex items-start justify-between mb-4">
                <div class="w-14 h-14 rounded-2xl border-4 border-white shadow-xl shrink-0 overflow-hidden group-hover:scale-110 transition-transform duration-500 relative" :style="getPanelTextureStyle(pnl.color_code, pnl.color_name || pnl.type)">
                  <div class="absolute inset-0 bg-black/5"></div>
                  <div class="w-full h-full rounded-[10px] border border-black/5"></div>
                </div>
                <div class="flex flex-col items-end gap-1.5 min-w-0">
                  <span class="text-[9px] font-black bg-slate-950 text-white px-2 py-0.5 rounded-lg uppercase tracking-widest">{{ pnl.finish_type || 'STD' }}</span>
                  <span :class="pnl.quantity <= 2 ? 'bg-rose-500 text-white animate-pulse' : 'bg-slate-100 text-slate-500'" class="text-[9px] font-black px-2 py-0.5 rounded-lg border border-slate-200/50">Stk: {{ pnl.quantity }}</span>
                </div>
              </div>

              <div class="flex-1 flex flex-col justify-between">
                <div>
                  <p class="font-black text-slate-900 text-[11px] uppercase tracking-tight group-hover:text-amber-800 transition-colors line-clamp-1 mb-1">{{ pnl.color_name || pnl.type }}</p>
                  <p class="text-[9px] text-slate-400 font-bold tracking-tight mb-4 flex items-center gap-1">
                    <span class="opacity-60">{{ pnl.color_code }}</span>
                    <span class="w-1 h-1 rounded-full bg-slate-200"></span>
                    <span>{{ pnl.size_x }}×{{ pnl.size_y }}</span>
                    <span class="w-1 h-1 rounded-full bg-slate-200"></span>
                    <span class="text-slate-500">{{ pnl.thickness }}mm</span>
                  </p>
                </div>

                <div class="flex items-center justify-between mt-auto pt-4 border-t border-slate-50">
                  <span class="text-[9px] text-slate-400 bg-slate-50 px-2 py-1 rounded-lg border border-slate-200/50 font-black uppercase tracking-tighter">{{ pnl.type }}</span>
                  <div class="flex flex-col items-end">
                    <span class="text-sm font-black text-slate-950 tracking-tight">{{ pnl.base_price_sell }} <span class="text-[10px] text-slate-400 font-bold ml-0.5">DH</span></span>
                  </div>
                </div>
              </div>
            </button>
          </div>
        </section>

        <!-- ── Bandchant ── -->
        <section v-show="filteredCantos.length > 0" class="fade-in">
          <div class="flex items-center gap-3 mb-5 px-1">
            <div class="w-10 h-10 rounded-2xl bg-[#CA8A04] text-slate-950 flex items-center justify-center shadow-lg shrink-0">
              <PaletteIcon class="w-5 h-5" />
            </div>
            <div>
              <span class="text-xs font-black text-slate-900 uppercase tracking-widest block leading-none">Chants & Bordures</span>
              <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1 block">PVC & Finitions</span>
            </div>
            <div class="flex-1 h-px bg-slate-200/60 ml-4"></div>
          </div>
          <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
            <button v-for="cnt in filteredCantos" :key="'c'+cnt.id"
              @click="cartStore.addToCart(cnt, 'canto')"
              class="group relative bg-white rounded-3xl p-4 text-left border border-slate-200/60 hover:border-amber-500/50 hover:shadow-[0_20px_50px_rgba(202,138,4,0.1)] transition-all duration-500 hover:-translate-y-1 active:scale-[0.98] overflow-hidden flex flex-col h-full">
              
              <div class="flex items-start justify-between mb-4">
                <div class="w-12 h-12 rounded-2xl border-4 border-white shadow-xl shrink-0 flex items-center justify-center group-hover:rotate-12 transition-transform duration-500" :style="{ background: getHexColor(cnt.color_code) }">
                  <div class="w-full h-full rounded-[10px] border border-black/5"></div>
                </div>
                <span class="text-[9px] font-black bg-slate-100 text-slate-500 px-2 py-0.5 rounded-lg border border-slate-200/50">{{ cnt.finish_type || 'STD' }}</span>
              </div>
              
              <div class="flex-1 flex flex-col justify-between">
                <div>
                  <p class="font-black text-slate-900 text-[11px] uppercase tracking-tight group-hover:text-amber-800 transition-colors line-clamp-1 mb-1">{{ cnt.color_name || cnt.name || 'BANDCHANT' }}</p>
                  <div class="flex items-center gap-2 mb-4">
                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">{{ cnt.color_code || 'N/A' }}</span>
                    <span v-if="cnt.width_mm" class="w-1 h-1 rounded-full bg-slate-200"></span>
                    <span v-if="cnt.width_mm" class="text-[9px] font-black text-slate-500">{{ cnt.width_mm }}×{{ cnt.thickness_mm }}mm</span>
                  </div>
                </div>

                <div class="flex items-center justify-between mt-auto pt-4 border-t border-slate-50">
                  <span :class="cnt.total_length_remaining <= 5 ? 'text-rose-600 bg-rose-50 border-rose-100' : 'text-slate-600 bg-slate-50 border-slate-200/50'" class="text-[9px] font-black px-2 py-1 rounded-lg border uppercase tracking-tighter">
                    {{ Number(cnt.total_length_remaining).toFixed(1) }}m
                  </span>
                  <span class="text-sm font-black text-slate-950 tracking-tight">{{ cnt.base_price_sell_per_m }} <span class="text-[10px] text-slate-400 font-bold ml-0.5">DH/m</span></span>
                </div>
              </div>
            </button>
          </div>
        </section>

        <!-- ── Consommables ── -->
        <section v-show="filteredConsumables.length > 0" class="fade-in">
          <div class="flex items-center gap-3 mb-5 px-1">
            <div class="w-10 h-10 rounded-2xl bg-slate-900 text-white flex items-center justify-center shadow-lg shrink-0">
              <PackageIcon class="w-5 h-5" />
            </div>
            <div>
              <span class="text-xs font-black text-slate-900 uppercase tracking-widest block leading-none">Quincaillerie</span>
              <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1 block">Consommables & Accessoires</span>
            </div>
            <div class="flex-1 h-px bg-slate-200/60 ml-4"></div>
          </div>
          <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
            <button v-for="con in filteredConsumables" :key="'con'+con.id"
              @click="cartStore.addToCart(con, 'consumable')"
              class="group relative bg-white rounded-3xl p-5 text-left border border-slate-200/60 hover:border-slate-950 hover:shadow-md transition-all duration-500 hover:-translate-y-1 active:scale-[0.98] overflow-hidden flex flex-col justify-between h-full min-h-[140px]">
              
              <div class="flex items-start justify-between mb-4">
                <div class="w-10 h-10 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center shrink-0 group-hover:bg-slate-900 group-hover:text-white transition-colors duration-300">
                  <PackageIcon class="w-5 h-5 text-slate-400 group-hover:text-inherit" />
                </div>
                <span :class="con.quantity_in_stock <= 5 ? 'bg-rose-500 text-white animate-pulse' : 'bg-slate-100 text-slate-500'"
                  class="text-[9px] font-black px-2 py-1 rounded-lg border border-slate-200/50 uppercase tracking-wider">
                  {{ con.quantity_in_stock }} {{ con.unit }}
                </span>
              </div>
              
              <div>
                <p class="font-black text-slate-900 text-xs uppercase tracking-tight group-hover:text-slate-950 transition-colors line-clamp-2 mb-4">{{ con.name }}</p>
                
                <div class="flex items-end justify-between pt-4 border-t border-slate-50">
                  <div class="flex flex-col">
                    <span class="text-[9px] text-slate-400 font-black uppercase tracking-widest mb-0.5">PV Unit</span>
                    <span class="text-sm font-black text-slate-950 tracking-tight">{{ con.base_price_sell }} <span class="text-[10px] text-slate-400 font-bold ml-0.5">DH</span></span>
                  </div>
                  <div class="w-8 h-8 rounded-full bg-slate-950 text-white flex items-center justify-center shadow-lg transform translate-x-2 opacity-0 group-hover:translate-x-0 group-hover:opacity-100 transition-all duration-300">
                    <PlusIcon class="w-4 h-4" />
                  </div>
                </div>
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
import { ScissorsIcon, LayersIcon, PaletteIcon, PackageIcon, PlusIcon } from 'lucide-vue-next';
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
