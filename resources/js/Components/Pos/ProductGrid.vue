<template>
  <!-- Main Content Area with Skeletons -->
  <div class="flex-1 overflow-y-auto p-8 space-y-10 custom-scrollbar bg-slate-50/50">
    
    <!-- Loading Skeletons -->
    <div v-if="isLoading" class="space-y-10 animate-pulse">
      <div v-for="i in 3" :key="'sk'+i">
        <div class="h-6 w-48 bg-slate-200 rounded-lg mb-6"></div>
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-5">
          <div v-for="j in 4" :key="'skc'+j" class="bg-white rounded-2xl border border-slate-100 p-5 h-36 flex flex-col justify-between">
            <div class="h-4 w-2/3 bg-slate-100 rounded"></div>
            <div class="h-8 w-1/2 bg-slate-100 rounded"></div>
          </div>
        </div>
      </div>
    </div>

    <template v-else>
      <!-- Category Filter Pills with Premium Glass Slider Styling -->
      <div class="flex flex-wrap gap-2.5 mb-8 p-1.5 bg-slate-100/80 backdrop-blur-md rounded-2xl border border-slate-200/40 w-fit">
        <button v-for="cat in categories" :key="cat.id" 
          @click="$emit('update:selectedCategory', cat.id)" 
          :class="selectedCategory === cat.id ? 'bg-white shadow-md text-brand-700 font-black scale-[1.03]' : 'text-slate-600 hover:text-slate-900 hover:bg-white/40 font-bold'" 
          class="px-6 py-2.5 rounded-xl text-xs uppercase tracking-widest transition-all duration-300 transform active:scale-95">
          {{ cat.label }}
        </button>
      </div>

      <!-- Category: Services -->
      <section v-show="filteredServices.length > 0" class="animate-fade-in">
        <h2 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-5 flex items-center">
          <div class="w-7 h-7 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center mr-2.5 border border-blue-100 shadow-sm"><ScissorsIcon class="w-3.5 h-3.5"/></div>
          Services de Coupe & Pose
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-5">
          <button v-for="srv in filteredServices" :key="'s'+srv.id" @click="cartStore.addToCart(srv, 'service')" 
            v-show="!srv.name.toLowerCase().includes('pose')"
            class="group card-3d bg-white rounded-3xl p-5 text-left border border-slate-200/60 shadow-[0_4px_20px_rgba(0,0,0,0.02)] hover:shadow-[0_12px_40px_rgba(59,130,246,0.12)] hover:border-blue-300 transition-all duration-300 hover:-translate-y-1.5 flex flex-col justify-between h-[13.8rem] relative overflow-hidden">
            
            <!-- Dynamic 3D back glow -->
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-blue-500/10 rounded-full blur-xl group-hover:scale-150 transition-transform duration-500"></div>

            <!-- Top Row: Icon with dynamic border & glow -->
            <div class="flex items-center space-x-3 mt-1 w-full">
              <div class="w-11 h-11 rounded-full border border-slate-100 bg-blue-50 flex items-center justify-center shrink-0 shadow-inner">
                <ScissorsIcon class="w-5 h-5 text-blue-500" />
              </div>
              <div class="flex-1 min-w-0">
                <span class="font-black text-slate-800 text-sm uppercase tracking-tight block group-hover:text-blue-700 transition-colors">{{ srv.name }}</span>
                <span class="text-[10px] font-bold text-slate-400 block mt-0.5">Prestation Technique</span>
              </div>
            </div>

            <!-- Middle Portion: Service details -->
            <div class="w-full bg-slate-50 border border-slate-100 rounded-2xl p-2.5 flex items-center justify-between group-hover:bg-blue-50/50 group-hover:border-blue-100/50 transition-all duration-300 mt-2">
              <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider">Unité de facturation</span>
              <span class="text-xs font-black text-blue-600 bg-blue-50 px-2.5 py-1 rounded-lg">Mètre</span>
            </div>

            <!-- Bottom Row: Price & Action Button -->
            <div class="flex items-center justify-between w-full mt-2 relative z-10">
              <div class="text-left">
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Tarif Prestation</span>
                <span class="text-base font-black text-blue-600">
                  {{ srv.unit_price }} <span class="text-[10px] text-blue-400 font-bold uppercase">DH/m</span>
                </span>
              </div>
              <div class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center opacity-0 transform translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300">
                <span class="text-lg font-black">+</span>
              </div>
            </div>
          </button>
          
          <!-- Customer Material Labor Button -->
          <button @click="cartStore.addCustomLabor('Pose Canto (Fourniture Client)', 2.00, 'mètre')" 
            class="group card-3d bg-white rounded-3xl p-5 text-left border border-amber-200 shadow-[0_4px_20px_rgba(0,0,0,0.02)] hover:shadow-[0_12px_40px_rgba(245,158,11,0.15)] hover:border-amber-400 transition-all duration-300 hover:-translate-y-1.5 flex flex-col justify-between h-[13.8rem] relative overflow-hidden">
            
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-amber-500/10 rounded-full blur-xl group-hover:scale-150 transition-transform duration-500"></div>

            <!-- Top Row: Icon with dynamic border & glow -->
            <div class="flex items-center space-x-3 mt-1 w-full">
              <div class="w-11 h-11 rounded-full border border-amber-100 bg-amber-50 flex items-center justify-center shrink-0 shadow-inner">
                <ScissorsIcon class="w-5 h-5 text-amber-500 animate-pulse" />
              </div>
              <div class="flex-1 min-w-0">
                <span class="font-black text-slate-800 text-sm uppercase tracking-tight block group-hover:text-amber-700 transition-colors">Pose Canto SEULE</span>
                <span class="text-[10px] font-black text-amber-600 block mt-0.5">Fourniture Client</span>
              </div>
            </div>

            <!-- Middle Portion: Service details -->
            <div class="w-full bg-amber-50/50 border border-amber-100/50 rounded-2xl p-2.5 flex items-center justify-between mt-2">
              <span class="text-[9px] font-black text-amber-500 uppercase tracking-wider">Unité de facturation</span>
              <span class="text-xs font-black text-amber-600 bg-white border border-amber-100 px-2.5 py-1 rounded-lg">Mètre</span>
            </div>

            <!-- Bottom Row: Price & Action Button -->
            <div class="flex items-center justify-between w-full mt-2 relative z-10">
              <div class="text-left">
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Tarif Prestation</span>
                <span class="text-base font-black text-amber-600">
                  2.00 <span class="text-[10px] text-amber-500 font-bold uppercase">DH/m</span>
                </span>
              </div>
              <div class="w-8 h-8 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center opacity-0 transform translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300">
                <span class="text-lg font-black">+</span>
              </div>
            </div>
          </button>
        </div>
      </section>

      <!-- Category: MDF/LATI -->
      <section v-show="filteredPanels.length > 0" class="animate-fade-in">
        <h2 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-5 flex items-center mt-10">
          <div class="w-7 h-7 rounded-xl bg-orange-50 text-orange-500 flex items-center justify-center mr-2.5 border border-orange-100 shadow-sm"><LayersIcon class="w-3.5 h-3.5"/></div>
          Panneaux (MDF / LATI)
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-5">
          <button v-for="pnl in filteredPanels" :key="'p'+pnl.id" @click="cartStore.addToCart(pnl, 'panel')" 
            class="group card-3d bg-white rounded-3xl p-5.5 text-left border border-slate-200/60 shadow-[0_4px_20px_rgba(0,0,0,0.02)] hover:shadow-[0_12px_40px_rgba(249,115,22,0.1)] hover:border-orange-300 transition-all duration-300 hover:-translate-y-1.5 flex flex-col justify-between h-[13.8rem] relative overflow-hidden">
            
            <!-- Finish & Stock Ribbon -->
            <div class="absolute top-3 right-3 z-10 flex flex-col items-end gap-1.5">
              <span class="bg-slate-900 text-white text-[9px] font-black px-2.5 py-1 rounded-lg uppercase tracking-widest shadow-sm">{{ pnl.finish_type || 'STD' }}</span>
              <span :class="pnl.quantity <= 2 ? 'bg-rose-500 text-white animate-pulse' : 'bg-slate-100 text-slate-600 border border-slate-200'" class="text-[9px] font-black px-2 py-0.5 rounded-md uppercase tracking-wider shadow-sm">
                Stock: {{ pnl.quantity }}
              </span>
            </div>

            <!-- Top Row: Wood texture circle + name -->
            <div class="flex items-center space-x-3 mt-1 w-full">
              <div class="w-11 h-11 rounded-full border-2 border-white shadow-[0_2px_8px_rgba(0,0,0,0.12)] transition-transform duration-300 group-hover:scale-110 flex items-center justify-center overflow-hidden shrink-0" :style="getPanelTextureStyle(pnl.color_code, pnl.color_name || pnl.type)">
                <div class="w-full h-full rounded-full border border-black/5"></div>
              </div>
              <div class="flex-1 min-w-0 pr-10">
                <span class="font-black text-slate-800 text-sm uppercase tracking-tight truncate block group-hover:text-orange-700 transition-colors">{{ pnl.color_name || pnl.type }}</span>
                <span class="text-[10px] font-bold text-slate-400 truncate block mt-0.5">{{ pnl.color_code || 'N/A' }}</span>
              </div>
            </div>

            <!-- Middle Portion: Dimensions & Spec details -->
            <div class="w-full bg-slate-50 border border-slate-100 rounded-2xl p-2.5 flex items-center justify-between group-hover:bg-orange-50/50 group-hover:border-orange-100/50 transition-all duration-300 mt-2">
              <div class="flex flex-col text-left">
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider">Format / Épaisseur</span>
                <span class="text-xs font-black text-slate-600 mt-0.5">
                  {{ pnl.size_x }}x{{ pnl.size_y }} <span class="text-slate-300">|</span> {{ pnl.thickness }}mm
                </span>
              </div>
              <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-2 py-0.5 bg-slate-100 rounded border border-slate-200/50">{{ pnl.type }}</span>
            </div>

            <!-- Bottom Row: Price & Action Button -->
            <div class="flex items-center justify-between w-full mt-2 relative z-10">
              <div class="text-left">
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Prix Panneau</span>
                <span class="text-base font-black text-orange-600">
                  {{ pnl.base_price_sell }} <span class="text-[10px] text-orange-400 font-bold uppercase">DH/pcs</span>
                </span>
              </div>
              <div class="w-8 h-8 rounded-full bg-orange-50 text-orange-600 flex items-center justify-center opacity-0 transform translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300">
                <span class="text-lg font-black">+</span>
              </div>
            </div>
          </button>
        </div>
      </section>

      <!-- Category: Bandchant -->
      <section v-show="filteredCantos.length > 0" class="animate-fade-in">
        <h2 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-5 flex items-center mt-10">
          <div class="w-7 h-7 rounded-xl bg-emerald-50 text-emerald-500 flex items-center justify-center mr-2.5 border border-emerald-100 shadow-sm"><PaletteIcon class="w-3.5 h-3.5"/></div>
          Bandchant (Canto)
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-5">
          <button v-for="cnt in filteredCantos" :key="'c'+cnt.id" @click="cartStore.addToCart(cnt, 'canto')" 
            class="group card-3d bg-white rounded-3xl p-5.5 text-left border border-slate-200/60 shadow-[0_4px_20px_rgba(0,0,0,0.02)] hover:shadow-[0_12px_40px_rgba(16,185,129,0.12)] hover:border-emerald-300/80 transition-all duration-300 hover:-translate-y-1.5 flex flex-col justify-between relative overflow-hidden h-[13.8rem]">
            
            <!-- Finish Badge (Top Right) -->
            <div class="absolute top-3 right-3 z-10">
              <span class="text-[9px] font-black bg-slate-100 text-slate-500 px-2.5 py-1 rounded-lg uppercase tracking-widest shadow-sm border border-slate-200/20">{{ cnt.finish_type || 'STD' }}</span>
            </div>

            <!-- Top Row: Color sample with dynamic border & glow -->
            <div class="flex items-center space-x-3 mt-1 w-full">
              <div class="w-11 h-11 rounded-full border-2 border-white shadow-[0_2px_8px_rgba(0,0,0,0.12)] transition-transform duration-300 group-hover:scale-110 flex items-center justify-center overflow-hidden shrink-0" :style="{ backgroundColor: getHexColor(cnt.color_code) }">
                <div class="w-full h-full rounded-full border border-black/5"></div>
              </div>
              <div class="flex-1 min-w-0 pr-10">
                <span class="font-black text-slate-800 text-sm uppercase tracking-tight truncate block group-hover:text-emerald-700 transition-colors">{{ cnt.color_name || cnt.name || 'BANDCHANT' }}</span>
                <span class="text-[10px] font-bold text-slate-400 truncate block mt-0.5">{{ cnt.color_code || 'N/A' }}</span>
              </div>
            </div>

            <!-- Middle Portion: Premium Stock Level Display (Meters) -->
            <div class="w-full bg-slate-50 border border-slate-100 rounded-2xl p-2.5 flex items-center justify-between group-hover:bg-emerald-50/50 group-hover:border-emerald-100/50 transition-all duration-300 mt-2">
              <div class="flex flex-col text-left">
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider">Mètres Restants</span>
                <span :class="cnt.total_length_remaining <= 5 ? 'text-rose-600 animate-pulse font-black' : 'text-emerald-600 font-black'" class="text-base leading-tight mt-0.5">
                  {{ Number(cnt.total_length_remaining).toFixed(1) }} <span class="text-xs font-black uppercase text-slate-500">M</span>
                </span>
              </div>
              <div :class="cnt.total_length_remaining <= 5 ? 'bg-rose-100 text-rose-600' : 'bg-emerald-100 text-emerald-600'" class="w-6 h-6 rounded-full flex items-center justify-center transition-colors shrink-0">
                <AlertTriangleIcon v-if="cnt.total_length_remaining <= 5" class="w-3.5 h-3.5" />
                <CheckIcon v-else class="w-3.5 h-3.5" />
              </div>
            </div>

            <!-- Bottom Row: Price per meter & Action Button -->
            <div class="flex items-center justify-between w-full mt-2 relative z-10">
              <div class="text-left">
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Prix Vente</span>
                <span class="text-base font-black text-emerald-600">
                  {{ cnt.base_price_sell_per_m }} <span class="text-[10px] text-emerald-400 font-bold uppercase">DH/m</span>
                </span>
              </div>
              <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center opacity-0 transform translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300">
                <span class="text-lg font-black">+</span>
              </div>
            </div>
          </button>
        </div>
      </section>

      <!-- Category: Consumables -->
      <section v-show="filteredConsumables.length > 0" class="animate-fade-in">
        <h2 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-5 flex items-center mt-10">
          <div class="w-7 h-7 rounded-xl bg-indigo-50 text-indigo-500 flex items-center justify-center mr-2.5 border border-indigo-100 shadow-sm"><PackageIcon class="w-3.5 h-3.5"/></div>
          Quincaillerie & Consommables
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-5">
          <button v-for="con in filteredConsumables" :key="'con'+con.id" @click="cartStore.addToCart(con, 'consumable')" 
            class="group card-3d bg-white rounded-3xl p-5 text-left border border-slate-200/60 shadow-[0_4px_20px_rgba(0,0,0,0.02)] hover:shadow-[0_12px_40px_rgba(79,70,229,0.12)] hover:border-indigo-300 transition-all duration-300 hover:-translate-y-1.5 flex flex-col justify-between h-[13.8rem] relative overflow-hidden">
            
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-indigo-500/10 rounded-full blur-xl group-hover:scale-150 transition-transform duration-500"></div>

            <!-- Top Row: Icon with dynamic border & glow -->
            <div class="flex items-center space-x-3 mt-1 w-full">
              <div class="w-11 h-11 rounded-full border border-slate-100 bg-indigo-50 flex items-center justify-center shrink-0 shadow-inner">
                <PackageIcon class="w-5 h-5 text-indigo-500" />
              </div>
              <div class="flex-1 min-w-0">
                <span class="font-black text-slate-800 text-sm uppercase tracking-tight block group-hover:text-indigo-700 transition-colors truncate">{{ con.name }}</span>
                <span class="text-[10px] font-bold text-slate-400 block mt-0.5">Quincaillerie</span>
              </div>
            </div>

            <!-- Middle Portion: Stock details -->
            <div class="w-full bg-slate-50 border border-slate-100 rounded-2xl p-2.5 flex items-center justify-between group-hover:bg-indigo-50/50 group-hover:border-indigo-100/50 transition-all duration-300 mt-2">
              <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider">Stock</span>
              <span :class="con.quantity_in_stock <= 5 ? 'text-rose-500 font-black bg-rose-50 border border-rose-100' : 'text-indigo-600 bg-indigo-50'" class="text-[10px] font-black px-2 py-0.5 rounded-md uppercase tracking-wider shadow-sm border border-transparent">
                Dispo: {{ con.quantity_in_stock }} {{ con.unit }}
              </span>
            </div>

            <!-- Bottom Row: Price & Action Button -->
            <div class="flex items-center justify-between w-full mt-2 relative z-10">
              <div class="text-left">
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Prix Vente</span>
                <span class="text-base font-black text-indigo-600">
                  {{ con.base_price_sell }} <span class="text-[10px] text-indigo-400 font-bold uppercase">DH/{{ con.unit }}</span>
                </span>
              </div>
              <div class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center opacity-0 transform translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300">
                <span class="text-lg font-black">+</span>
              </div>
            </div>
          </button>
        </div>
      </section>
    </template>

  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useCartStore } from '@/stores/cart';
import { ScissorsIcon, LayersIcon, PaletteIcon, PackageIcon, CheckIcon, AlertTriangleIcon } from 'lucide-vue-next';

const props = defineProps({
  isLoading: Boolean,
  searchQuery: String,
  selectedCategory: String,
  services: Array,
  panels: Array,
  cantos: Array,
  consumables: Array
});

const emit = defineEmits(['update:selectedCategory']);

const cartStore = useCartStore();

const categories = [
  { id: 'all', label: 'Tous' },
  { id: 'panel', label: 'Panneaux' },
  { id: 'canto', label: 'Bandchant' },
  { id: 'service', label: 'Services' },
  { id: 'consumable', label: 'Quincaillerie' }
];

const filteredPanels = computed(() => {
  if (props.selectedCategory !== 'all' && props.selectedCategory !== 'panel') return [];
  const q = props.searchQuery.toLowerCase();
  return props.panels.filter(p => 
    (p.type || '').toLowerCase().includes(q) ||
    (p.color_name || '').toLowerCase().includes(q) ||
    (p.color_code || '').toLowerCase().includes(q)
  );
});

const filteredCantos = computed(() => {
  if (props.selectedCategory !== 'all' && props.selectedCategory !== 'canto') return [];
  const q = props.searchQuery.toLowerCase();
  return props.cantos.filter(c => 
    (c.name || '').toLowerCase().includes(q) || 
    (c.color_name || '').toLowerCase().includes(q) || 
    (c.color_code || '').toLowerCase().includes(q)
  );
});

const filteredServices = computed(() => {
  if (props.selectedCategory !== 'all' && props.selectedCategory !== 'service') return [];
  return props.services.filter(s => s.name.toLowerCase().includes(props.searchQuery.toLowerCase()));
});

const filteredConsumables = computed(() => {
  if (props.selectedCategory !== 'all' && props.selectedCategory !== 'consumable') return [];
  return props.consumables.filter(c => c.name.toLowerCase().includes(props.searchQuery.toLowerCase()));
});

import { commonTextures } from '@/colors';

const getHexColor = (code) => {
  if (!code) return '#f1f5f9';
  const c = code.toUpperCase();
  
  // Look up exact hex color from commonTextures
  const texture = commonTextures.find(t => t.code.toUpperCase() === c);
  if (texture && texture.hex) {
    return texture.hex;
  }

  // Fallback to basic matching
  if (c.includes('BLANC')) return '#ffffff';
  if (c.includes('CHENE') || c.includes('TERRA')) return '#b45309';
  if (c.includes('NOIR')) return '#1e293b';
  if (c.includes('112') || c.includes('GRIS')) return '#e2e8f0';
  
  // Fallback to random hash
  let hash = 0;
  for (let i = 0; i < c.length; i++) { hash = c.charCodeAt(i) + ((hash << 5) - hash); }
  let color = '#';
  for (let i = 0; i < 3; i++) {
    let value = (hash >> (i * 8)) & 0xFF;
    color += ('00' + value.toString(16)).substr(-2);
  }
  return color;
};

// act as design engineer: generate beautiful warm digital textures representing panels
const getPanelTextureStyle = (colorCode, colorName) => {
  if (colorCode) {
    const texture = commonTextures.find(t => t.code.toUpperCase() === colorCode.toUpperCase());
    if (texture && texture.hex) {
      return {
        background: texture.hex,
        boxShadow: `0 4px 12px ${texture.hex}40`
      };
    }
  }

  const name = (colorName || '').toLowerCase();
  if (name.includes('oak') || name.includes('chêne')) {
    return {
      background: 'linear-gradient(135deg, #eab308 0%, #b45309 100%)',
      boxShadow: '0 4px 12px rgba(180, 83, 9, 0.25)'
    };
  }
  if (name.includes('walnut') || name.includes('noyer')) {
    return {
      background: 'linear-gradient(135deg, #78350f 0%, #451a03 100%)',
      boxShadow: '0 4px 12px rgba(69, 26, 3, 0.25)'
    };
  }
  if (name.includes('elm') || name.includes('orme')) {
    return {
      background: 'linear-gradient(135deg, #a8a29e 0%, #78716c 100%)',
      boxShadow: '0 4px 12px rgba(120, 113, 108, 0.25)'
    };
  }
  if (name.includes('blanc') || name.includes('white')) {
    return {
      background: 'linear-gradient(135deg, #f8fafc 0%, #cbd5e1 100%)',
      boxShadow: '0 4px 12px rgba(148, 163, 184, 0.15)'
    };
  }
  
  if (colorCode) {
     const hex = getHexColor(colorCode);
     if (hex !== '#f1f5f9') {
        return {
           background: hex,
           boxShadow: `0 4px 12px ${hex}40`
        };
     }
  }
  
  // Default elegant warm wood gradient
  return {
    background: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
    boxShadow: '0 4px 12px rgba(217, 119, 6, 0.2)'
  };
};

</script>

<style scoped>
.card-3d {
  position: relative;
  transition: transform 0.4s cubic-bezier(0.165, 0.84, 0.44, 1), border-color 0.3s ease, box-shadow 0.4s ease;
  transform-style: preserve-3d;
  perspective: 1000px;
}
.card-3d:hover {
  transform: perspective(1000px) rotateX(3deg) rotateY(-3deg) translateZ(8px) scale(1.025);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.01);
}
/* Shine overlay */
.card-3d::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(255,255,255,0.4) 0%, rgba(255,255,255,0) 60%);
  opacity: 0;
  transition: opacity 0.4s ease;
  pointer-events: none;
  z-index: 5;
}
.card-3d:hover::after {
  opacity: 1;
}

.animate-fade-in {
  animation: fadeIn 0.5s ease-out both;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
