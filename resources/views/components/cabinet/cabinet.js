

Vue.component('component-cabinet', {
    template: '{{html}}',
    props: ['data'],

    data: function(){return{

        oldPassword: '',
        newPassword: '',
        passwordConfirmation: '',
        errorForm: '',

        domain: ''

    }},

    methods: {


        addDomain: function () {
            console.log('start');
           
            this.getMethod('domains.add', {
                domain: this.domain
            }, (function(response){
                if(response.response.status){
                    this.data.listDomains.push(response.response.data);

                    if(!this.data.update){
                        this.postMethod('tikets.add', {
                            tupe: 'domain',
                            title: 'Модерация домена '+ this.domain,
                            message: 'Проверка домена - '+ this.domain +' на право размещения плеера.',
                            data: JSON.stringify( {domain: this.domain, id: response.response.data.id} )
                        }, (function(response){
                            console.log(response);
                        }).bind(this));
                    }

                }
            }).bind(this));
        },

        deleteDomain: function (id, index) {
            this.getMethod('domains.delete', {
                id: id
            }, (function(){
                this.data.listDomains.splice(index, 1);
                
            }).bind(this));
        },


        checkForm: function (e) {

            if(this.passwordConfirmation != this.newPassword){
                this.errorForm = 'Новые пароли не совпадают';
                e.preventDefault();
            }

            if(this.newPassword.length < 6){
                this.errorForm = 'Длинна пароля не менее 8 символов';
                e.preventDefault();
            }

            if (this.oldPassword == '' && this.newPassword == '' && this.passwordConfirmation == '') {
                this.errorForm = 'Заполните все поля'; 
                e.preventDefault();
            }

            return true;
        }
        
    }
});