<template>
  <v-form>
    <v-container fluid>
      <v-row>
        <v-col
          cols="12"
          md="6"
          sm="6"
        >
          <v-text-field
            v-model="item.title"
            :error-messages="nameErrors"
            :label="$t('name')"
            required
            @blur="$v.item.title.$touch()"
            @input="$v.item.title.$touch()"
          />
        </v-col>

        <v-col
          cols="12"
          md="6"
          sm="6"
        >
          <v-text-field
            v-model="item.code"
            :error-messages="codeErrors"
            :label="$t('code')"
            required
            @blur="$v.item.code.$touch()"
            @input="$v.item.code.$touch()"
          />
        </v-col>
      </v-row>
    </v-container>
  </v-form>
</template>

<script>
import has from "lodash/has"
import useVuelidate from "@vuelidate/core"
import { required } from "@vuelidate/validators"

export default {
  name: "CourseCategoryForm",
  setup() {
    return { v$: useVuelidate() }
  },
  props: {
    values: {
      type: Object,
      required: true,
    },

    errors: {
      type: Object,
      default: () => {},
    },

    initialValues: {
      type: Object,
      default: () => {},
    },
  },
  mounted() {},
  data() {
    return {}
  },
  computed: {
    // eslint-disable-next-line
    item() {
      return this.initialValues || this.values
    },

    nameErrors() {
      const errors = []

      if (!this.$v.item.title.$dirty) return errors

      has(this.violations, "title") && errors.push(this.violations.title)

      !this.$v.item.title.required && errors.push(this.$t("Field is required"))

      return errors
    },
    codeErrors() {
      const errors = []

      if (!this.$v.item.code.$dirty) return errors

      has(this.violations, "code") && errors.push(this.violations.code)

      !this.$v.item.code.required && errors.push(this.$t("Field is required"))

      return errors
    },

    violations() {
      return this.errors || {}
    },
  },
  methods: {},
  validations: {
    item: {
      name: {
        required,
      },
      code: {
        required,
      },
    },
  },
}
</script>
