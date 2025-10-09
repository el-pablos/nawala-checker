<template>
  <div>
    <h1>Nawala Checker Dashboard</h1>
    <div v-if="stats">
      <div class="stats">
        <div class="stat">
          <h3>Total Targets</h3>
          <p>{{ stats.total_targets }}</p>
        </div>
        <div class="stat">
          <h3>Blocked</h3>
          <p>{{ stats.blocked_targets }}</p>
        </div>
        <div class="stat">
          <h3>Accessible</h3>
          <p>{{ stats.ok_targets }}</p>
        </div>
        <div class="stat">
          <h3>Unknown</h3>
          <p>{{ stats.unknown_targets }}</p>
        </div>
      </div>
      <div v-if="stats.accessible_percentage !== undefined" class="percentages">
        <p>Accessible: {{ stats.accessible_percentage }}%</p>
        <p>Blocked: {{ stats.blocked_percentage }}%</p>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  stats: Object
})
</script>

