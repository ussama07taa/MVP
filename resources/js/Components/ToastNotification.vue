<template>
  <Teleport to="body">
    <div class="fixed top-6 right-6 z-[9999] flex flex-col gap-4 pointer-events-none" style="max-width: 400px;">
      <TransitionGroup name="toast">
        <div v-for="toast in toasts" :key="toast.id"
          class="pointer-events-auto relative group rounded-2xl border backdrop-blur-md shadow-premium overflow-hidden flex items-start gap-4 p-4 pr-10 min-w-[320px] transition-all duration-300"
          :class="styles[toast.type]">
          
          <!-- Icon Badge -->
          <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 mt-0.5 shadow-sm border border-black/5"
               :class="iconBg[toast.type]">
            <CheckCircleIcon v-if="toast.type === 'success'" class="w-5 h-5" />
            <XCircleIcon v-else-if="toast.type === 'error'" class="w-5 h-5" />
            <AlertTriangleIcon v-else-if="toast.type === 'warning'" class="w-5 h-5" />
            <InfoIcon v-else class="w-5 h-5" />
          </div>

          <!-- Content -->
          <div class="flex-1 space-y-1">
            <h4 class="text-xs font-black uppercase tracking-widest opacity-60">{{ toast.type }}</h4>
            <p class="text-sm font-semibold leading-relaxed">{{ toast.message }}</p>
          </div>

          <!-- Close Button -->
          <button @click="remove(toast.id)"
                  class="absolute top-2 right-2 p-1.5 rounded-lg opacity-0 group-hover:opacity-100 hover:bg-black/5 transition-all cursor-pointer">
            <XIcon class="w-4 h-4 opacity-40 hover:opacity-100" />
          </button>

          <!-- Progress Bar -->
          <div v-if="toast.duration !== Infinity"
               class="absolute bottom-0 left-0 h-1 bg-black/10 transition-all duration-linear"
               :style="{ width: '100%', animation: `toast-progress ${toast.duration}ms linear forwards` }">
          </div>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { useToast } from '@/composables/useToast';
import { CheckCircleIcon, XCircleIcon, AlertTriangleIcon, InfoIcon, XIcon } from 'lucide-vue-next';

const { toasts, remove } = useToast();

const styles = {
  success: 'bg-white/95 border-emerald-200 text-emerald-900 shadow-emerald-500/5',
  error: 'bg-white/95 border-rose-200 text-rose-900 shadow-rose-500/5',
  warning: 'bg-white/95 border-amber-200 text-amber-900 shadow-amber-500/5',
  info: 'bg-white/95 border-blue-200 text-blue-900 shadow-blue-500/5',
};

const iconBg = {
  success: 'bg-emerald-50 text-emerald-600',
  error: 'bg-rose-50 text-rose-600',
  warning: 'bg-amber-50 text-amber-600',
  info: 'bg-blue-50 text-blue-600',
};
</script>

<style scoped>
@keyframes toast-progress {
  from { width: 100%; }
  to { width: 0%; }
}

.toast-enter-active {
  transition: all 0.5s cubic-bezier(0.18, 0.89, 0.32, 1.28);
}
.toast-leave-active {
  transition: all 0.4s cubic-bezier(0.55, 0, 1, 0.45);
}
.toast-enter-from {
  opacity: 0;
  transform: translateX(100%) scale(0.9);
}
.toast-leave-to {
  opacity: 0;
  transform: translateX(100%) scale(0.9) translateY(-10px);
}
</style>

