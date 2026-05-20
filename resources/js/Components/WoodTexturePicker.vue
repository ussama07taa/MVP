<template>
  <div class="mt-2 bg-white p-5 rounded-3xl border border-slate-100 shadow-sm flex flex-col gap-5">
    
    <!-- 3D INTERACTIVE PREVIEW PLATE & LOUPE ZOOM (Dak trick dayl zoom) -->
    <div class="flex flex-col items-center select-none">
       <!-- 3D Perspective Card Container -->
       <div 
         class="w-full h-44 rounded-2xl relative overflow-hidden border border-black/5 cursor-crosshair transition-all duration-300 shadow-[0_15px_35px_rgba(0,0,0,0.06)] group/plate"
         :style="{ 
           backgroundColor: activeHex,
           transform: `perspective(800px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.02)`,
           transformStyle: 'preserve-3d'
         }"
         @mousemove="handleMouseMove"
         @mouseleave="handleMouseLeave"
       >
         <!-- Wood texture overlay -->
         <div v-if="!isMarble" class="absolute inset-0 opacity-30 bg-[url('https://www.transparenttextures.com/patterns/wood-pattern.png')]"></div>
         
         <!-- Marble texture overlay (Veins) -->
         <div v-else class="absolute inset-0 opacity-[0.25] bg-[url('https://www.transparenttextures.com/patterns/white-diamond.png')]">
           <!-- Dynamic CSS procedural marble vein streaks -->
           <div class="absolute inset-0 bg-gradient-to-tr from-transparent via-black/10 to-transparent rotate-45 scale-150 blur-[1px]"></div>
           <div class="absolute inset-0 bg-gradient-to-bl from-transparent via-white/20 to-transparent -rotate-12 scale-110 blur-[2px]"></div>
         </div>

          <!-- High Gloss Mirror Specular Reflexes -->
          <div v-if="isHighGloss" class="absolute inset-0 bg-gradient-to-tr from-white/10 via-white/40 to-white/60 mix-blend-screen pointer-events-none opacity-95 shadow-[inset_0_6px_30px_rgba(255,255,255,0.7)] transition-all duration-300">
             <div class="absolute inset-0 bg-gradient-to-br from-white/30 via-white/10 to-transparent rotate-12 scale-150 transform"></div>
             <!-- Specular highlight streak -->
             <div class="absolute -inset-x-20 top-0 h-10 bg-gradient-to-b from-white/35 to-transparent blur-sm -skew-y-12 animate-pulse"></div>
          </div>

          <!-- Glass reflections / Glossy overlay -->
          <div class="absolute inset-0 bg-gradient-to-tr from-white/0 via-white/10 to-white/25 mix-blend-overlay pointer-events-none"></div>

         <!-- Board Depth representation (3D edge thickness highlight) -->
         <div class="absolute inset-x-0 bottom-0 h-1.5 bg-black/10 border-t border-white/15"></div>

         <!-- Dynamic 3D Floating Tag -->
         <div 
           class="absolute bottom-4 left-4 bg-slate-950/80 backdrop-blur-md px-3.5 py-2 rounded-xl text-white border border-white/10 shadow-lg transition-transform duration-300"
           style="transform: translateZ(25px)"
         >
           <p class="text-[9px] font-black text-brand-400 uppercase tracking-widest leading-none mb-1">Échantillon Pro</p>
           <p class="text-xs font-black uppercase tracking-tight leading-none text-white">{{ activeName || 'SÉLECTION' }}</p>
         </div>

         <!-- Interactive Magnifying Loupe (Zoom Glass) -->
         <div 
           v-if="showLoupe"
           class="absolute w-24 h-24 rounded-full border-4 border-white shadow-[0_10px_30px_rgba(0,0,0,0.25)] pointer-events-none overflow-hidden flex items-center justify-center animate-fade-in"
           :style="{ 
             left: `${loupeX - 48}px`, 
             top: `${loupeY - 48}px`,
             backgroundColor: activeHex,
             transform: 'translateZ(40px) scale(1.15)',
           }"
         >
            <!-- Zoomed wood texture inside the loupe -->
            <div v-if="!isMarble" class="absolute inset-0 opacity-40 bg-[url('https://www.transparenttextures.com/patterns/wood-pattern.png')]" :style="{ transform: 'scale(2.2)', backgroundPosition: `${loupePercentX}% ${loupePercentY}%` }"></div>
            <!-- Zoomed marble texture inside the loupe -->
            <div v-else class="absolute inset-0 opacity-40 bg-[url('https://www.transparenttextures.com/patterns/white-diamond.png')]" :style="{ transform: 'scale(2.5)', backgroundPosition: `${loupePercentX}% ${loupePercentY}%` }">
               <div class="absolute inset-0 bg-gradient-to-tr from-transparent via-black/20 to-transparent rotate-45 scale-[2.5] blur-[1px]"></div>
            </div>
            
            <!-- High Gloss specular highlight inside Loupe -->
            <div v-if="isHighGloss" class="absolute inset-0 rounded-full bg-gradient-to-tr from-white/20 via-white/40 to-white/70 mix-blend-screen opacity-90 shadow-[inset_0_4px_16px_rgba(255,255,255,0.7)]"></div>

            <!-- Zoom indicator glass reflection ring -->
            <div class="absolute inset-0 rounded-full border border-black/10 shadow-inner bg-gradient-to-tr from-white/0 via-white/20 to-white/30 mix-blend-overlay"></div>
            
            <!-- Center Crosshair dot -->
            <div class="w-1.5 h-1.5 rounded-full bg-white/40 shadow-inner"></div>
         </div>
       </div>

       <!-- Subheader details -->
       <div class="text-center mt-3.5">
          <p class="text-xs font-black text-slate-800 uppercase tracking-tight">{{ activeName || 'Choisir une texture' }}</p>
          <p class="text-[9px] font-bold text-slate-400 font-mono tracking-widest mt-0.5 uppercase">{{ modelValue || 'AUCUN CODE' }}</p>
       </div>
    </div>

    <!-- Search & Palette Section -->
    <div class="pt-4 border-t border-slate-100">
      <div class="flex items-center justify-between mb-4 gap-4">
        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Palette de Textures</label>
        
        <!-- Mini Search -->
        <div class="relative flex-1 max-w-[200px]">
          <SearchIcon class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input v-model="searchQuery" type="text" placeholder="Chercher un code..." 
                 class="w-full pl-9 pr-3 py-2 bg-slate-50 border-slate-100 rounded-xl text-[10px] font-bold focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
        </div>
      </div>

      <!-- Scrollable Grid -->
      <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-4 lg:grid-cols-4 xl:grid-cols-5 gap-3 max-h-[220px] overflow-y-auto pr-2 custom-scrollbar">
        <div v-for="(texture, index) in filteredTextures" :key="index" @click="selectTexture(texture)" 
          class="cursor-pointer group relative flex flex-col items-stretch p-1.5 rounded-xl border transition-all duration-200" 
          :class="modelValue === texture.code ? 'border-brand-500 bg-brand-50 shadow-md ring-2 ring-brand-500/10' : 'border-slate-100 hover:border-brand-200 hover:shadow-sm bg-white'">
          
          <!-- Texture Swatch -->
          <div class="aspect-square w-full rounded-lg mb-1.5 shadow-sm border border-black/5 relative overflow-hidden" 
               :style="{ backgroundColor: texture.hex }">
             <div v-if="!texture.name.toLowerCase().includes('marmo') && !texture.name.toLowerCase().includes('marbre')" class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/wood-pattern.png')]"></div>
             <div v-else class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/white-diamond.png')]"></div>
          </div>
          
          <div class="px-0.5 text-center">
            <p class="text-[8px] font-black text-slate-900 leading-tight truncate uppercase">{{ texture.name.replace(/Marmo |Marbre /gi, '') }}</p>
            <p class="text-[7px] font-bold text-slate-400 font-mono tracking-tighter">{{ texture.code }}</p>
          </div>

          <!-- Selection Badge -->
          <div v-if="modelValue === texture.code" class="absolute -top-1 -right-1 bg-brand-600 text-white p-0.5 rounded-full shadow-lg border-2 border-white">
            <CheckIcon class="h-2 w-2" />
          </div>
        </div>

        <!-- No Results -->
        <div v-if="filteredTextures.length === 0" class="col-span-full py-8 text-center">
           <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Aucun résultat</p>
        </div>
      </div>

      <!-- Manual Input -->
      <div class="mt-4 pt-4 border-t border-slate-50 flex items-center justify-between">
        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Code Actuel :</span>
        <input type="text" :value="modelValue" @input="$emit('update:modelValue', $event.target.value.toUpperCase())" 
               class="w-32 text-center bg-slate-900 text-white rounded-xl py-2 text-xs font-black uppercase tracking-widest focus:ring-2 focus:ring-brand-500 border-none shadow-lg">
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { commonTextures } from '../colors';
import { SearchIcon, CheckIcon } from 'lucide-vue-next';

const props = defineProps(['modelValue']);
const emit = defineEmits(['update:modelValue', 'textureSelected']);

const searchQuery = ref('');

// 3D Perspective States
const rotateX = ref(0);
const rotateY = ref(0);

// Loupe Zoom States
const showLoupe = ref(false);
const loupeX = ref(0);
const loupeY = ref(0);
const loupePercentX = ref(50);
const loupePercentY = ref(50);

const activeTexture = computed(() => {
  return commonTextures.find(t => t.code === props.modelValue);
});

const activeHex = computed(() => {
  return activeTexture.value ? activeTexture.value.hex : '#f1f5f9';
});

const activeName = computed(() => {
  return activeTexture.value ? activeTexture.value.name : 'Sélectionner';
});

const isMarble = computed(() => {
  if (!activeName.value) return false;
  const n = activeName.value.toLowerCase();
  return n.includes('marmo') || n.includes('marbre') || n.includes('béton') || n.includes('concrete') || n.includes('terrazzo') || n.includes('ardoise');
});

const isHighGloss = computed(() => {
  if (!activeName.value) return false;
  const n = activeName.value.toLowerCase();
  return n.includes('high gloss') || n.includes('hg') || n.includes('luisant');
});

const handleMouseMove = (e) => {
  const el = e.currentTarget;
  const rect = el.getBoundingClientRect();
  const x = e.clientX - rect.left;
  const y = e.clientY - rect.top;
  
  const xc = rect.width / 2;
  const yc = rect.height / 2;
  
  // Calculate perspective 3D angles (Max 10deg rotation)
  rotateX.value = -((y - yc) / yc) * 10;
  rotateY.value = ((x - xc) / xc) * 10;
  
  // Loupe Coordinates
  loupeX.value = x;
  loupeY.value = y;
  
  // Compute zoomed background position coordinates in percentage
  loupePercentX.value = (x / rect.width) * 100;
  loupePercentY.value = (y / rect.height) * 100;
  
  showLoupe.value = true;
};

const handleMouseLeave = () => {
  rotateX.value = 0;
  rotateY.value = 0;
  showLoupe.value = false;
};

const filteredTextures = computed(() => {
  if (!searchQuery.value) return commonTextures;
  const q = searchQuery.value.toLowerCase();
  return commonTextures.filter(t => 
    t.name.toLowerCase().includes(q) || 
    t.code.toLowerCase().includes(q)
  );
});

const selectTexture = (texture) => {
  emit('update:modelValue', texture.code);
  emit('textureSelected', texture);
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 3px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f8fafc; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }

.animate-fade-in {
  animation: fadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateZ(40px) scale(0.85); }
  to { opacity: 1; transform: translateZ(40px) scale(1.15); }
}
</style>
