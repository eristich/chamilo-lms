<template>
  <div class="flex items-center gap-2">
    <BaseButton
      :label="label"
      :size="size"
      icon="attachment"
      type="primary"
      @click="showFileDialog"
    />
    <p class="text-gray-90">
      {{ fileName }}
    </p>
    <input
      ref="inputFile"
      :accept="acceptFileType"
      class="hidden"
      type="file"
    />
  </div>
</template>

<script setup>
import BaseButton from "./BaseButton.vue"
import { computed, onMounted, ref } from "vue"
import { sizeValidator } from "./validators"

const props = defineProps({
  modelValue: {
    type: [File, null],
    required: true,
  },
  label: {
    type: String,
    required: true,
  },
  accept: {
    type: String,
    default: "",
    validator: (value) => {
      if (value === "") {
        return true
      }
      return ["image"].includes(value)
    },
  },
  size: {
    type: String,
    default: "normal",
    validator: sizeValidator,
  },
})

const emit = defineEmits(["fileSelected"])

const inputFile = ref(null)
const fileName = ref("")

const acceptFileType = computed(() => {
  switch (props.accept) {
    case "":
      return ""
    case "image":
      return "image/*"
    default:
      return ""
  }
})

onMounted(() => {
  inputFile.value.addEventListener("change", fileSelected)
})

const fileSelected = () => {
  let file = inputFile.value.files[0]
  fileName.value = file.name
  emit("fileSelected", file)
}

const showFileDialog = () => {
  inputFile.value.click()
}
</script>
