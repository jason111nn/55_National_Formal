<template>
  <div class="plate-interactive" aria-labelledby="plate-title">
    <h3 id="plate-title" class="sr-only" aria-hidden="true">餐盤示意圖</h3>
    
    <div class="plate-base">
      <div 
        v-for="food in foods" 
        :key="food.id"
        class="plate-slice"
        role="button"
        :aria-expanded="activeFood?.id === food.id"
        aria-controls="plate-overlay"
        @click="showOverlay(food)"
      >
        {{ food.icon }}
      </div>

      <!-- 彈窗，包含修復 NVDA 無障礙的 aria-modal 與 focus lock 結構 -->
      <div 
        id="plate-overlay" 
        class="plate-overlay" 
        role="dialog"
        aria-modal="true"
        :aria-hidden="!activeFood"
        v-show="activeFood"
        @click.self="activeFood = null"
      >
        <button class="close-overlay" aria-label="關閉內容" @click="activeFood = null">X</button>
        <div class="overlay-content" v-if="activeFood">
          <span class="o-icon">{{ activeFood.icon }}</span>
          <h4 class="o-title">{{ activeFood.title }}</h4>
          <p class="o-portion">{{ activeFood.portion }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const activeFood = ref(null)
const foods = [
  { id: 'fruit', title: '水果類', portion: '1份', icon: '🍎' },
  { id: 'veg', title: '蔬菜類', portion: '約半碗', icon: '🥬' }
]

const showOverlay = (food) => {
  activeFood.value = food
}
</script>
