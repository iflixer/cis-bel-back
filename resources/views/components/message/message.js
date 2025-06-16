

Vue.component('component-message', {
    template: '{{html}}',
    props: ['value', 'index', 'name'],
    methods: {

        deleteMesage: function(index){
            this.$root.$emit('deleteMesage', index);
        },

        putActivated: function(url, index){
            this.$root.$emit('getMessage', [{tupe: 'info', message: 'На почту отправленна ссылка'}] );
            this.$root.$emit('deleteMesage', index);
            this.$root.$emit('updateUrl', url);
        }
        
    }
});