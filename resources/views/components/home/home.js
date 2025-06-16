

Vue.component('component-home', {
    template: '{{html}}',
    props: ['data'],
    methods: {

        controlPanel: function(id, name) {
            this.$root.$emit('getPanel', id, name);
        }
        
    }
});