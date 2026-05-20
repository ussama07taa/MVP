<template>
  <div class="min-h-screen bg-slate-900 pb-20 font-sans select-none">
    
    <!-- Ultra-Simple Header -->
    <header class="bg-slate-900 px-6 py-6 border-b border-white/10 flex items-center justify-between">
      <div class="flex items-center gap-4">
        <div class="w-12 h-12 bg-brand-500 rounded-2xl flex items-center justify-center shadow-lg shadow-brand-500/20">
          <HammerIcon class="w-7 h-7 text-white" />
        </div>
        <h1 class="text-2xl font-black text-white tracking-tight">Khedma Diyali</h1>
      </div>
      <div class="flex items-center gap-3">
        <!-- Audio Toggle Button -->
        <button @click="toggleMute" class="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center active:scale-90 transition-transform" :class="isMuted ? 'text-rose-400' : 'text-emerald-400'" :title="isMuted ? 'Activer le son' : 'Couper le son'">
          <component :is="isMuted ? VolumeXIcon : Volume2Icon" class="w-7 h-7" />
        </button>
        
        <button @click="fetchQueue" class="w-14 h-14 bg-white/5 rounded-2xl text-white flex items-center justify-center active:scale-90 transition-transform">
          <RefreshCwIcon :class="{'animate-spin': isLoading}" class="w-7 h-7" />
        </button>
      </div>
    </header>

    <main class="p-4 space-y-6">
      
      <!-- Big Progress Summary -->
      <div v-if="queue.length > 0" class="px-2">
         <div class="flex justify-between items-end mb-2">
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Khdamat kamlin</span>
            <span class="text-xl font-black text-brand-400">{{ totalDonePercent }}%</span>
         </div>
         <div class="w-full h-4 bg-white/5 rounded-full overflow-hidden">
            <div class="h-full bg-brand-500 transition-all duration-1000 shadow-[0_0_15px_rgba(var(--color-brand-500),0.5)]" :style="{ width: totalDonePercent + '%' }"></div>
         </div>
      </div>

      <!-- Search & Material Filters -->
      <div v-if="queue.length > 0" class="px-2 space-y-4">
        <!-- Search bar -->
        <div class="relative">
          <SearchIcon class="w-6 h-6 absolute left-4 top-1/2 transform -translate-y-1/2 text-slate-500" />
          <input v-model="searchQuery" type="text" placeholder="Chercher un client ou ticket..." class="w-full pl-12 pr-4 py-4 bg-white/5 border border-white/10 rounded-[1.5rem] focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 text-white font-bold text-sm transition-all placeholder:text-slate-500" />
        </div>
        
        <!-- Horizontal Scrollable Material tags -->
        <div v-if="uniqueMaterials.length > 0" class="flex gap-2 overflow-x-auto pb-2 scrollbar-none">
          <button @click="selectedMaterial = 'all'" class="px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest border shrink-0 transition-all"
            :class="selectedMaterial === 'all' ? 'bg-brand-500 border-brand-400 text-white shadow-lg shadow-brand-500/20' : 'bg-white/5 border-white/10 text-slate-400'">
            Tous
          </button>
          <button v-for="mat in uniqueMaterials" :key="mat" @click="selectedMaterial = mat" class="px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest border shrink-0 transition-all"
            :class="selectedMaterial === mat ? 'bg-brand-500 border-brand-400 text-white shadow-lg shadow-brand-500/20' : 'bg-white/5 border-white/10 text-slate-400'">
            {{ mat }}
          </button>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="!isLoading && queue.length === 0" class="py-24 text-center">
        <div class="w-32 h-32 bg-white/5 rounded-[3rem] flex items-center justify-center mx-auto mb-8 border border-white/5">
          <CheckCircle2Icon class="w-16 h-16 text-slate-700" />
        </div>
        <h3 class="text-2xl font-black text-white uppercase tracking-widest">Ma3endek walou</h3>
        <p class="text-slate-500 mt-2 font-bold">Kolchi sala, rta7 chwiya!</p>
      </div>

      <!-- Empty Search State -->
      <div v-if="!isLoading && queue.length > 0 && filteredQueue.length === 0" class="py-16 text-center">
        <h4 class="text-lg font-black text-slate-500 uppercase tracking-widest">Walo ma-kayn b had l-ism</h4>
        <p class="text-slate-600 mt-2 font-bold">Essayer de chercher autre chose.</p>
      </div>

      <!-- Tactical Job Cards -->
      <div v-for="job in filteredQueue" :key="job.id" 
        class="bg-white/5 border border-white/10 rounded-[3rem] overflow-hidden transition-all duration-300"
        :class="{'ring-4 ring-brand-500/30 border-brand-500/50 bg-slate-800/50': job.status === 'in_progress'}">
        
        <!-- Job Info Header -->
        <div class="p-6 border-b border-white/5 flex items-center justify-between bg-white/[0.02]">
           <div class="flex items-center gap-5">
              <div class="w-16 h-16 bg-white text-slate-900 rounded-[1.5rem] flex items-center justify-center shadow-xl">
                <span class="text-2xl font-black">{{ job.queue_number.replace('Q-', '') }}</span>
              </div>
              <div>
                <h3 class="text-xl font-black text-white leading-tight mb-1">{{ job.client_name }}</h3>
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full" :class="job.status === 'in_progress' ? 'bg-blue-500 animate-pulse' : 'bg-slate-600'"></span>
                  <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.1em]">
                    {{ job.status === 'in_progress' ? 'Khdam fih deba' : 'F nouba' }}
                  </span>
                </div>
              </div>
           </div>
           <div class="text-right">
              <div class="text-xl font-black text-white">{{ job.waiting_since }}</div>
              <div class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Saa</div>
           </div>
        </div>

        <!-- Huge Task Buttons -->
        <div class="p-6 grid grid-cols-1 gap-4">
          <button v-for="service in job.services" :key="service.id" 
            @click="toggleService(service)"
            class="relative w-full p-8 rounded-[2rem] border-4 transition-all duration-300 flex items-center justify-between active:scale-95 group"
            :class="service.is_done ? 'bg-emerald-600 border-emerald-400 shadow-lg shadow-emerald-500/20' : 'bg-slate-800/50 border-slate-700/50'">
            
              <div class="flex items-center gap-8">
                <!-- Massive Quantity Badge -->
                <div class="w-20 h-20 rounded-3xl flex flex-col items-center justify-center shadow-lg transition-all"
                  :class="service.is_done ? 'bg-white/20 text-white' : 'bg-brand-500 text-white shadow-brand-500/20'">
                  <span class="text-xs font-black uppercase opacity-60 leading-none mb-1">Qty</span>
                  <span class="text-3xl font-black leading-none">{{ service.quantity }}</span>
                </div>
                
                <div class="text-left">
                   <span class="block text-2xl font-black transition-colors" :class="service.is_done ? 'text-white' : 'text-slate-300'">
                    {{ service.label }}
                   </span>
                   
                   <div class="flex flex-wrap gap-2 mt-2">
                      <div v-if="service.material_type" class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest border"
                        :class="service.is_done ? 'bg-white/10 border-white/20 text-white' : 'bg-slate-700/50 border-slate-600 text-slate-400'">
                        {{ service.material_type }}
                      </div>
                      <div v-if="service.material_color" class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest border"
                        :class="service.is_done ? 'bg-white/10 border-white/20 text-white' : 'bg-brand-500/10 border-brand-500/20 text-brand-400'">
                        {{ service.material_color }}
                      </div>
                   </div>

                   <div v-if="service.is_done" class="text-[10px] font-black uppercase tracking-[0.2em] text-white/60 mt-3 flex items-center gap-2">
                      <CheckCircle2Icon class="w-3 h-3" /> Mission terminée
                   </div>
                </div>
              </div>

            <!-- Big Checkbox Status -->
            <div class="w-12 h-12 rounded-full flex items-center justify-center border-4 transition-all"
              :class="service.is_done ? 'bg-white border-white text-emerald-600' : 'bg-transparent border-slate-600 text-transparent'">
              <CheckIcon class="w-7 h-7 stroke-[4px]" />
            </div>
            
            <!-- Animated Completion Indicator -->
            <div v-if="service.is_done" class="absolute inset-0 bg-gradient-to-r from-emerald-500/0 via-white/5 to-emerald-500/0 -translate-x-full group-hover:translate-x-full transition-transform duration-1000 pointer-events-none"></div>
          </button>
        </div>

        <!-- Big "Finish & Hide" Button -->
        <Transition name="fade">
          <div v-if="job.all_done" class="px-6 pb-8 animate-in zoom-in duration-500">
             <button @click="hideJob(job)" class="w-full py-8 bg-emerald-600 text-white rounded-[2rem] font-black text-2xl uppercase tracking-[0.2em] shadow-2xl shadow-emerald-600/40 active:scale-95 transition-all flex items-center justify-center gap-4">
                <CheckCircle2Icon class="w-10 h-10" /> SALIT !
             </button>
          </div>
        </Transition>

        <!-- Notes (Clear Box) -->
        <div v-if="job.notes" class="px-8 py-6 bg-amber-500/10 border-t border-white/5 flex items-start gap-4">
           <InfoIcon class="w-6 h-6 text-amber-500 shrink-0 mt-0.5" />
           <p class="text-sm font-black text-amber-200/80 leading-snug italic">"{{ job.notes }}"</p>
        </div>
      </div>
    </main>

    <!-- Bottom Instruction Bar -->
    <div class="fixed bottom-0 left-0 right-0 p-4 bg-slate-900/95 backdrop-blur-md border-t border-white/5 z-50">
       <div class="flex items-center justify-center gap-3 py-2">
          <div class="w-3 h-3 rounded-full bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.5)]"></div>
          <span class="text-xs font-black text-white uppercase tracking-[0.2em]">Wre9 deba o dghat ila saliti</span>
       </div>
    </div>

    <!-- Huge Success Notification -->
    <Transition name="slide-up">
      <div v-if="toast" class="fixed inset-0 z-[100] flex items-center justify-center p-6 pointer-events-none">
        <div class="bg-emerald-600 text-white px-10 py-8 rounded-[3rem] shadow-[0_20px_50px_rgba(0,0,0,0.5)] flex flex-col items-center gap-4 border-4 border-emerald-400 animate-bounce pointer-events-auto">
          <CheckCircle2Icon class="w-20 h-20" />
          <span class="text-2xl font-black uppercase tracking-widest text-center">{{ toast }}</span>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { 
  RefreshCwIcon, CheckIcon, HammerIcon, 
  InfoIcon, CheckCircle2Icon, ClockIcon, ScissorsIcon, 
  LayoutIcon, DrillIcon, WrenchIcon, LayersIcon,
  Volume2Icon, VolumeXIcon, SearchIcon
} from 'lucide-vue-next';

const queue = ref([]);
const isLoading = ref(false);
const toast = ref(null);
let pollInterval = null;

// Search & Filtering State
const searchQuery = ref('');
const selectedMaterial = ref('all');

const uniqueMaterials = computed(() => {
  const mats = new Set();
  queue.value.forEach(job => {
    if (job.services) {
      job.services.forEach(s => {
        if (s.material_color) mats.add(s.material_color);
        else if (s.material_type) mats.add(s.material_type);
      });
    }
  });
  return Array.from(mats);
});

const filteredQueue = computed(() => {
  let res = queue.value;
  
  // Filter by search query (Client name or Queue number)
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase();
    res = res.filter(job => 
      job.client_name.toLowerCase().includes(q) || 
      job.queue_number.toLowerCase().includes(q)
    );
  }
  
  // Filter by selected material (raw panel type/color)
  if (selectedMaterial.value !== 'all') {
    res = res.filter(job => 
      job.services && job.services.some(s => 
        s.material_color === selectedMaterial.value || 
        s.material_type === selectedMaterial.value
      )
    );
  }
  
  return res;
});

// Premium Audio Engine (Uses Browser Web Audio API Synthesizer)
const isMuted = ref(localStorage.getItem('workshop_muted') === 'true');

const playSynthesizedSound = (freqs, type = 'sine', duration = 0.1) => {
  if (isMuted.value) return;
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    let time = ctx.currentTime;
    freqs.forEach(([freq, delay, vol]) => {
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.type = type;
      osc.frequency.setValueAtTime(freq, time + delay);
      gain.gain.setValueAtTime(vol || 0.1, time + delay);
      gain.gain.exponentialRampToValueAtTime(0.0001, time + delay + duration);
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start(time + delay);
      osc.stop(time + delay + duration);
    });
  } catch (e) {
    console.error('AudioContext synthesis failed', e);
  }
};

// Ascent smart chime for brand-new jobs sent from the cashier
const playNewJobChime = () => {
  playSynthesizedSound([
    [523.25, 0, 0.15],      // C5
    [659.25, 0.08, 0.15],   // E5
    [783.99, 0.16, 0.15],   // G5
    [1046.50, 0.24, 0.2]    // C6
  ], 'triangle', 0.45);
};

// High-pitch satisfying ding for service checkoff
const playCheckoffDing = () => {
  playSynthesizedSound([
    [880, 0, 0.1],          // A5
    [1760, 0.04, 0.15]      // A6
  ], 'sine', 0.25);
};

// Celebration major chord for completing a full client ticket (SALIT!)
const playTriumphFanfare = () => {
  playSynthesizedSound([
    [523.25, 0, 0.15],      // C5
    [659.25, 0.04, 0.15],   // E5
    [783.99, 0.08, 0.15],   // G5
    [1318.51, 0.12, 0.2]    // E6
  ], 'sine', 0.55);
};

const toggleMute = () => {
  isMuted.value = !isMuted.value;
  localStorage.setItem('workshop_muted', isMuted.value ? 'true' : 'false');
  if (!isMuted.value) {
    playCheckoffDing(); // Play test ding to verify speaker output
  }
};



const totalDonePercent = computed(() => {
  if (queue.value.length === 0) return 0;
  const total = queue.value.reduce((acc, job) => acc + job.services_total, 0);
  const done = queue.value.reduce((acc, job) => acc + job.services_done, 0);
  return total > 0 ? Math.round((done / total) * 100) : 0;
});

const getServiceIcon = (label) => {
  const l = label.toLowerCase();
  if (l.includes('decoupe') || l.includes('cut')) return ScissorsIcon;
  if (l.includes('canto') || l.includes('chant')) return LayersIcon;
  if (l.includes('perçage') || l.includes('drill')) return LayoutIcon;
  if (l.includes('assemblage') || l.includes('mount')) return WrenchIcon;
  return HammerIcon;
};

const toggleService = async (service) => {
  try {
    const originalValue = service.is_done;
    service.is_done = !service.is_done;
    
    if (window.navigator.vibrate) window.navigator.vibrate([30, 50, 30]);
    
    if (!originalValue) {
      playCheckoffDing();
      showToast('NADI !');
    }
    
    await axios.post(`/api/workshop/services/${service.id}/toggle`);
    await fetchQueue();
  } catch (error) {
    console.error('Toggle error', error);
    fetchQueue();
  }
};

const hideJob = async (job) => {
  try {
    if (window.navigator.vibrate) window.navigator.vibrate([100, 50, 100]);
    playTriumphFanfare();
    await axios.post(`/api/workshop/queue/${job.id}/hide`);
    showToast('mchaat !');
    await fetchQueue();
  } catch (error) {
    console.error('Hide error', error);
  }
};

const showToast = (msg) => {
  toast.value = msg;
  setTimeout(() => { toast.value = null; }, 2000);
};

const fetchQueue = async (force = false) => {
  if (isLoading.value && !force) return;
  isLoading.value = true;
  try {
    const response = await axios.get('/api/workshop/queue');
    
    // Compare job IDs to trigger brand new job chimes
    const oldIds = queue.value.map(j => j.id);
    const newIds = response.data.map(j => j.id);
    const hasNewJob = newIds.some(id => !oldIds.includes(id));
    if (queue.value.length > 0 && hasNewJob) {
      playNewJobChime();
    }
    
    queue.value = response.data;
  } catch (error) {
    console.error('Fetch error', error);
  } finally {
    isLoading.value = false;
  }
};

const handleVisibilityChange = () => {
  if (document.visibilityState === 'visible') {
    fetchQueue(true);
    startPolling();
  } else {
    stopPolling();
  }
};

const startPolling = () => {
  stopPolling();
  pollInterval = setInterval(() => fetchQueue(), 15000); // Snappy 15 seconds polling
};

const stopPolling = () => {
  if (pollInterval) clearInterval(pollInterval);
};

onMounted(() => {
  fetchQueue();
  startPolling();
  document.addEventListener('visibilitychange', handleVisibilityChange);
});

onUnmounted(() => {
  stopPolling();
  document.removeEventListener('visibilitychange', handleVisibilityChange);
});
</script>

<style scoped>

.slide-up-enter-active, .slide-up-leave-active {
  transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.slide-up-enter-from, .slide-up-leave-to {
  opacity: 0;
  transform: translateY(100px) scale(0.8);
}
</style>
