<template>
  <Toolbar
    :handle-reset="resetForm"
    :handle-submit="onSendForm"
  />

  <DocumentsForm
    ref="createForm"
    :errors="violations"
    :values="item"
  />
  <Loading :visible="isLoading" />
</template>

<script>
import { mapActions } from "vuex"
import { createHelpers } from "vuex-map-fields"
import DocumentsForm from "../../components/documents/Form.vue"
import Loading from "../../components/Loading.vue"
import Toolbar from "../../components/Toolbar.vue"
import CreateMixin from "../../mixins/CreateMixin"
import { RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"

const servicePrefix = "Documents"

const { mapFields } = createHelpers({
  getterType: "documents/getField",
  mutationType: "documents/updateField",
})

export default {
  name: "DocumentsCreate",
  servicePrefix,
  components: {
    Loading,
    Toolbar,
    DocumentsForm,
  },
  mixins: [CreateMixin],
  data() {
    return {
      item: {},
      type: "folder",
    }
  },
  computed: {
    ...mapFields(["error", "isLoading", "created", "violations"]),
  },
  created() {
    this.item.parentResourceNodeId = this.$route.params.node
    this.item.resourceLinkList = JSON.stringify([
      {
        gid: this.$route.query.gid,
        sid: this.$route.query.sid,
        cid: this.$route.query.cid,
        visibility: RESOURCE_LINK_PUBLISHED, // visible by default
      },
    ])
  },
  methods: {
    ...mapActions("documents", ["createWithFormData", "reset"]),
  },
}
</script>
