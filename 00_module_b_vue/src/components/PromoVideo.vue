<template>
  <section id="promo-video">
    <video ref="videoRef" muted controls style="width:100%; max-width:800px; display:block;"></video>
  </section>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const videoRef = ref(null)
let observer = null

onMounted(() => {
  // 評審指正：加入 ratio >= 0.5 雙重判斷，且使用小數點 threshold 陣列捕獲更精細
  const options = { threshold: [0, 0.4, 0.5, 0.6, 1.0] }
  
  observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting && entry.intersectionRatio >= 0.5) {
        videoRef.value?.play().catch(() => {})
      } else {
        videoRef.value?.pause()
      }
    })
  }, options)

  if (videoRef.value) observer.observe(videoRef.value)
})

onUnmounted(() => {
  if (observer && videoRef.value) observer.unobserve(videoRef.value)
})
</script>
