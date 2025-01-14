
let api = 'https://xx2.digeam.com/api/shop';
// let user = 'jacky0996';
// let user = '';


const app = Vue.createApp({
    delimiters: ["%[", "]"],
    data() {
        return {
            loading: false,
            mask: false,
            mask2: false,
            popS: false,
            popB: false,
            popA: false,
            isClose: '',
            isInfo: '',
            isBuy: '',
            isMsg: '',
            login: -99,
            user: {
                account: '',
                point: '',
                totalSpend: '',
                charList: [],
                buyList: [],
                spend: '',
            },
            navTab: 'produce',
            liTab: 'hot',
            swiper: null,
            carousel: {
            },
            produceData: [],
            buyId: {},
            getId: {},
            pop_info: {
                img: '',
                name: '',
                info: '',
                start: null,
                end: null,
            },
            pop_buy: {
                id: '',
                name: '',
                num: '',
                point: '',
                total: '',
            },
            selectedCharId: '',
            selectedServeId: '',
            feedBack: '',
        }
    },
    computed: {
        sortItem() {
            let hotItem = this.produceData.filter(i => i.cate == 1);
            let specialItem = this.produceData.filter(i => i.cate == 2);
            return {
                hotItem: hotItem,
                specialItem: specialItem,
            }
        }
    },
    watch:{
        resetId() {
            this.resetBuyId();
        }
    },
    methods: {
        checkCookie(name) { 
            const cookies = document.cookie.split(';');
            const cookieName = `${name}=`;
            for (let i = 0; i < cookies.length; i++) {
                let cookie = cookies[i].trim();
                if (cookie.indexOf(cookieName) == 0) {
                    return cookie.substring(cookieName.length, cookie.length);
                }
            }
            return '';
        },
        async getSetting() {
            this.loading = true;
            try {
                const res = await axios.post(api, {
                    type: 'login',
                    user: 'jacky0996',
                });
                if (res.data.status == 1) {
                    this.login = 1;
                    this.user.point = res.data.point;
                    this.user.charList = res.data.char_list;
                    this.user.buyList = res.data.buy_list;
                    this.user.spend = res.data.spend;
                }
                this.carousel = res.data.banner;
                this.feedBack = res.data.feedback;
                console.log(this.feedBack );
                // this.feedBack = '';
                this.produceData = res.data.item;
                console.log('api傳的', res.data);
                console.log(this.feedBack);
                return res;
            } catch (err) {
                console.log(err);
            } finally {
                console.log('api,end');
                this.loading = false;
            }
        },
        initSwiper() {
            // 確保在初始化 Swiper 之前清理掉舊的實例
            if (this.swiper) {
                console.log('swiper重啟');
                this.swiper.destroy();
            }
            // 延遲初始化以確保 DOM 正確渲染
            this.$nextTick(() => {
                this.swiper = new Swiper(".swiper", {
                    slidesPerView: 1,
                    spaceBetween: 24,
                    loop: true,
                    speed:1000,
                    autoplay:true,
                    disableOnInteraction: false,
                    delay: 1000,
                    breakpoints: {
                        1920: {
                            slidesPerView: 1,
                            slidesPerGroup: 1,
                        },
                    },
                    navigation: {
                        nextEl: ".swiper-button-next",
                        prevEl: ".swiper-button-prev",
                    },
                    pagination: {
                        el: ".swiper-pagination",
                        clickable: true,
                    },
                });
            });
        },
        changeNav(id) {
            this.navTab = id;
            if (id == 'produce') {
                this.initSwiper();
            }
            this.resetBuyId();
        },
        changeLi(id) {
            this.liTab = id;
            this.resetBuyId();
        },
        resetBuyId() {
            Object.keys(this.buyId).forEach(key => {
                this.buyId[key] = 1;
            });
        },
        itemInfo(id) {
            this.mask = true;
            this.popS = true;
            this.isInfo = true;
            let info = this.produceData.filter(i => i.id == id);
            this.pop_info.img = info[0].img;
            this.pop_info.name = info[0].title;
            this.pop_info.info = info[0].description;
            this.pop_info.start = info[0].limit_start;
            this.pop_info.end = info[0].limit_end;
        },
        close() {
            this.mask = false;
            this.popS = false;
            this.popA = false;
            this.popB = false;
            this.isInfo = false;
            this.isBuy = false;
        },
        changeNum(event, id) {
            this.buyId[id] = event.target.value;
            console.log(this.buyId[id]);
        },
        buyPop(num, id, title, point) {
            this.mask = true;
            this.popS = true;
            this.isBuy = true;
            this.pop_buy.id = id;
            this.pop_buy.name = title;
            this.pop_buy.num = num;
            this.pop_buy.point = point;
            this.pop_buy.total = num * point;
            console.log(this.pop_buy);
        },
        async buy(id, num) {
            console.log(id,num);
            this.loading = true;
            this.popS = false;
            try {
                const res = await axios.post(api, {
                    type: 'buy_item',
                    user: this.user.account,
                    item_id: id,
                    count: num
                });
                this.popA = true;
                if (res.data.status == 1) {
                    this.getSetting();
                    this.isMsg = res.data.msg;
                } else if (res.data.status == -99) {
                    this.isMsg = '請先登入';
                } else if (res.data.status == -98) {
                    this.isMsg = '點數不足，無法購買';
                } else if (res.data.status == -97) {
                    this.isMsg = '限購商品已售完';
                } else if (res.data.status == -96) {
                    this.isMsg = '商品不在可購買時間內';
                }
                return res;
            } catch (err) {
                console.log(err);
            } finally {
                console.log('Buy,end');
                this.loading = false;
            };
        },
        changeGetItemNum(event, id) {
            this.getId[id] = event.target.value;
            console.log(this.getId[id]);
        },
        async getItem(serve, char, id, num) {
            console.log(serve, char, id, num);
            if (serve == 0 || char == 0) {
                this.mask = true;
                this.popA = true;
                this.isMsg = '請選擇伺服器及角色後領取';
                return
            }
            this.loading = true;
            try {
                const res = await axios.post(api, {
                    type: 'get_item',
                    user: this.user.account,
                    item_id: id,
                    server_id: serve,
                    char_id: char,
                    count: num
                });
                this.mask = true;
                this.popA = true;
                if (res.data.status == 1) {
                    this.getSetting();
                    this.isMsg = res.data.msg;
                } else {
                    console.log(res.data.status, res.data.msg);
                    this.isMsg = '錯誤，請重新整理頁面';
                }
                return res;
            } catch (err) {
                console.log(err);
            } finally {
                console.log('get,end');
                this.loading = false;
            };
        }
    },
    mounted() {
        if (this.checkCookie('StrID')) {
            this.user.account = this.checkCookie('StrID');
        }

        this.getSetting();
    }
});

app.mount('#wrap');


const swiper = new Swiper(".swiper", {
    slidesPerView: 1,
    spaceBetween: 24,
    loop: true,
    speed:1000,
    autoplay:true,
    disableOnInteraction: false,
    delay: 1000,
    breakpoints: {
        1920: {
            slidesPerView: 1,
            slidesPerGroup: 1,
        },
    },
    navigation: {
        nextEl: ".swiper-button-next",
        prevEl: ".swiper-button-prev",
    },
    pagination: {
        el: ".swiper-pagination",
        clickable: true,
    },
});
