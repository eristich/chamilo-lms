<template>
  <BaseToolbar>
    <BaseButton
      :label="t('Back')"
      icon="back"
      type="black"
      @click="back"
    />
  </BaseToolbar>
  <div class="flex flex-col justify-start">
    <div class="mb-4">
      <Dashboard
        :plugins="['Webcam', 'ImageEditor']"
        :props="{
          proudlyDisplayPoweredByUppy: false,
          width: '100%',
          height: '350px',
        }"
        :uppy="uppy"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from "vue"
import "@uppy/core/dist/style.css"
import "@uppy/dashboard/dist/style.css"
import "@uppy/image-editor/dist/style.css"
import Uppy from "@uppy/core"
import Webcam from "@uppy/webcam"
import { Dashboard } from "@uppy/vue"
import { useRoute, useRouter } from "vue-router"
import { ENTRYPOINT } from "../../config/entrypoint"
import { useCidReq } from "../../composables/cidReq"
import { useUpload } from "../../composables/upload"
import { useI18n } from "vue-i18n"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import { useStore } from "vuex"
import { RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"

const XHRUpload = require("@uppy/xhr-upload")
const ImageEditor = require("@uppy/image-editor")

const store = useStore()
const route = useRoute()
const router = useRouter()
const { gid, sid, cid } = useCidReq()
const { onCreated } = useUpload()
const { t } = useI18n()
const filetype = route.query.filetype === "certificate" ? "certificate" : "file"

const showAdvancedSettings = ref(false)
const isUncompressZipEnabled = ref(false)
const fileExistsOption = ref("rename")

const parentResourceNodeId = ref(Number(route.query.parentResourceNodeId || route.params.node))
const resourceLinkList = ref(
  JSON.stringify([
    {
      gid,
      sid,
      cid,
      visibility: RESOURCE_LINK_PUBLISHED,
    },
  ]),
)

const uppy = ref(
  new Uppy()
    .use(ImageEditor, {
      cropperOptions: {
        viewMode: 1,
        background: false,
        autoCropArea: 1,
        responsive: true,
      },
      actions: {
        revert: true,
        rotate: true,
        granularRotate: true,
        flip: true,
        zoomIn: true,
        zoomOut: true,
        cropSquare: true,
        cropWidescreen: true,
        cropWidescreenVertical: true,
      },
    })
    .use(XHRUpload, {
      endpoint: ENTRYPOINT + "personal_files",
      formData: true,
      fieldName: "uploadFile",
    })
    .on("upload-success", (item, response) => {
      onCreated(response.body)
    })
    .on("complete", () => {
      console.log("Upload complete, sending message...")
      const parentNodeId = parentResourceNodeId.value
      localStorage.setItem("isUploaded", "true")
      localStorage.setItem("uploadParentNodeId", parentNodeId)
      setTimeout(() => {
        if (route.query.returnTo) {
          router.push({
            name: route.query.returnTo,
            params: { node: parentNodeId },
            query: { ...route.query, parentResourceNodeId: parentNodeId },
          })
        } else {
          router.push({
            name: "FileManagerList",
            params: { node: parentNodeId },
            query: { ...route.query, parentResourceNodeId: parentNodeId },
          })
        }
      }, 2000)
    }),
)

uppy.value.setMeta({
  filetype,
  parentResourceNodeId: parentResourceNodeId.value,
  resourceLinkList: resourceLinkList.value,
  isUncompressZipEnabled: isUncompressZipEnabled.value,
  fileExistsOption: fileExistsOption.value,
})

if (filetype === "certificate") {
  uppy.value.opts.restrictions.allowedFileTypes = [".html"]
} else {
  uppy.value.use(Webcam)
}

watch(isUncompressZipEnabled, () => {
  uppy.value.setOptions({
    meta: {
      isUncompressZipEnabled: isUncompressZipEnabled.value,
    },
  })
})

watch(fileExistsOption, () => {
  uppy.value.setOptions({
    meta: {
      fileExistsOption: fileExistsOption.value,
    },
  })
})

function back() {
  let queryParams = { cid, sid, gid, filetype, tab: route.query.tab }
  if (route.query.tab) {
    router.push({
      name: "FileManagerList",
      params: { node: parentResourceNodeId.value },
      query: queryParams,
    })
  } else {
    router.push({
      name: "FileManagerList",
      params: { node: 0 },
      query: queryParams,
    })
  }
}
</script>
