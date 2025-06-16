

const tupeUser = {
    data: function(){return {}},
    props: ['tupe'],
    template: '\
    <div class="tikets-s__stat">\
        <span v-if="tupe == \'administrator\'" class="tikets-s__item-tupe">Администратор</span>\
        <span v-if="tupe == \'managing\'" class="tikets-s__item-tupe">Менджер</span>\
        <span v-if="tupe == \'redactor\'" class="tikets-s__item-tupe">Редактор</span>\
        <span v-if="tupe == \'client\'" class="tikets-s__item-tupe">Клиент</span>\
    </div>'
}

const statusUser = {
    data: function(){return {}},
    props: ['status'],
    template: '\
    <div class="tikets-s__stat">\
        <span v-if="status == 0" class="display__string-color--red">Заблокированн</span>\
        <span v-if="status == 1" class="display__string-color--blue">Активен</span>\
        <span v-if="status == 2" class="display__string-color--grin">Подтвержден</span>\
    </div>'
}



Vue.component('component-users', {
    template: '{{html}}',
    props: ['data'],
    components:{
        'tupe-user': tupeUser,
        'status-user': statusUser,
    },
    data: function(){return{

        modal: false,
        usersList: null,
        pageUsers: false,
        modalPage: {
            user: false
        },

        newUser: {
            login: '',
            password: '',
            tupe: 'client',
            email: '',
            name: '',
            endname: ''
        },

        validList: [
            { item: 'login', error: false, text: 'Незаполнен логин' },
            { item: 'password', error: false, text: 'Незаполнен пароль' },
            { item: 'email', error: false, text: 'Незаполнен email' }
        ],
        errorList: []

    }},

    created: function () {
        this.getUsers();
    },

    methods: {

        getUsers: function(tupe){
            this.getMethod('users.get', {
                close: this.pageUsers,
                tupe: tupe
            },(function(response){
                // console.log(response);
                this.usersList = response.data.map(function(element){ 
                    return { element: element, chek: false };
                });
            }).bind(this));
        },

        openModal: function(modal){
            this.modal = true;
            this.modalPage[modal] = true;
        },

        closeModal: function(){
            this.modal = false;
            for (const key in this.modalPage){
                this.modalPage[key] = false;
            }
        },

        addUser: function(){
            this.modal = true;
            this.modalPage.user = true;
        },
        addUserSend: function(){
            
            this.errorList = [];
            for (let i = 0; i < this.validList.length; i++) {
                this.validList[i].error = false;
            }

            for (let i = 0; i < this.validList.length; i++) {
                if( this.newUser[ this.validList[i].item ] == '' ){
                    this.validList[i].error = true;
                    this.errorList.push(this.validList[i].text);
                }
            }

            if(this.errorList.length != 0){ return; }

            
            this.postMethod('users.add', 
                this.newUser
            , (function(response){
                // console.log(response);

                this.closeModal();
                this.getUsers();
                this.newUser.password = '';
                

            }).bind(this));
            

        },

        putStatusUser: function(status){
            const ids = this.usersList
            .filter(function(element){ return element.chek; })
            .map(function(element){ return element.element.id; });

            this.getMethod('users.putStatus', {
                ids: ids,
                status: status
            },(function(response){
                // console.log(response);
                this.getUsers();
            }).bind(this));
        },



        openPageUsers: function(open){
            this.pageUsers = open;
            this.getUsers();
        },


        generateKey: function(){

            let pass = "";
            const strong = 12;
            const dic = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
         
            for (let i = 0; i < strong; i++){
                pass += dic.charAt(Math.floor(Math.random() * dic.length));
            }

            return pass;
        },

        /*
        function generate() {
            
        }
        */
        //JSON.stringify(this.updateFilmData)




        getMessageList: function(id){
            this.tiketMessages = null;
            this.getMethod('tikets.getId', {
                id: id
            },(function(response){
                console.log(response);

                this.tiketMessages = response.data;
                
                this.tiketMessages.map((function(el){ 
                    el.created_at = this.getDataS(el.created_at);
                    return el;
                }).bind(this));

            }).bind(this));
        },

        sendTiket: function(){
            this.postMethod('tikets.add', {
                tupe: 'tiket',
                title: this.titleTiket,
                message: this.textTiket
            }, (function(response){
                //console.log(response);
                this.closeModal();
                this.init();
            }).bind(this));
        },
        
        
    }
});