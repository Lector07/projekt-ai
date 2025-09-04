<template>
  <div>
    <div class="flex border-b border-gray-200 dark:border-gray-700">
      <button
        v-for="(tab, idx) in tabs"
        :key="idx"
        @click="$emit('update:modelValue', idx)"
        class="px-6 py-3 text-base font-medium transition-all duration-200 border-b-2 focus:outline-none"
        :class="[
          modelValue === idx
            ? 'border-nova-primary text-nova-primary'
            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
        ]"
      >
        <slot :name="`tab-${idx}`">{{ tab }}</slot>
      </button>
    </div>
    <div class="pt-4">
      <slot :name="`panel-${modelValue}`"></slot>
    </div>
  </div>
</template>

<script setup lang="ts">
import { defineProps, defineEmits } from 'vue';
const props = defineProps<{
  tabs: string[];
  modelValue: number;
}>();
const emit = defineEmits(['update:modelValue']);
</script>

