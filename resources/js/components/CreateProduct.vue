<template>
    <section>

        <div v-if="message" class="alert alert-success alert-dismissible fade show" role="alert">
            {{ message }}
            <button type="button" class="close" @click="message = ''" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

        <form v-on:submit.prevent="saveProduct($event)" enctype="multipart/form-data" ref="form">
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="">Product Name</label>
                            <input type="text" v-model="product_name" placeholder="Product Name" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="">Product SKU</label>
                            <input type="text" v-model="product_sku" placeholder="Product Name" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="">Description</label>
                            <textarea v-model="description" id="" cols="30" rows="4" class="form-control"></textarea>
                        </div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Media</h6>
                    </div>
                    <div class="card-body border">
                        <vue-dropzone ref="myVueDropzone" id="dropzone" :duplicateCheck="true" :destroyDropzone="true" :options="dropzoneOptions" v-on:vdropzone-success="vdropzoneSuccess"  v-on:vdropzone-mounted="fileAttachDropzone"></vue-dropzone>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Variants</h6>
                    </div>
                    <div class="card-body">
                        <div class="row" v-for="(item,index) in product_variant">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="">Option</label>
                                    <select v-model="item.option" class="form-control">
                                        <option v-for="variant in variants"
                                                :value="variant.id">
                                            {{ variant.title }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label v-if="product_variant.length != 1" @click="product_variant.splice(index,1); checkVariant"
                                           class="float-right text-primary"
                                           style="cursor: pointer;">Remove</label>
                                    <label v-else for="">.</label>
                                    <input-tag v-model="item.tags" @input="checkVariant" class="form-control"></input-tag>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer" v-if="product_variant.length < variants.length && product_variant.length < 3">
                        <button @click="newVariant($event)" type="button" class="btn btn-primary">Add another option</button>
                    </div>

                    <div class="card-header text-uppercase">Preview</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr>
                                    <td>Variant</td>
                                    <td>Price</td>
                                    <td>Stock</td>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="variant_price in product_variant_prices">
                                    <td>{{ variant_price.title }}</td>
                                    <td>
                                        <input type="text" class="form-control" v-model="variant_price.price">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" v-model="variant_price.stock">
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <input type="checkbox" v-model="back_index"> Redirect to product list?
        <button type="submit" class="btn btn-lg btn-primary">Save</button>
        <button type="button" class="btn btn-secondary btn-lg">Cancel</button>
        </form>
    </section>
</template>

<script>
import vue2Dropzone from 'vue2-dropzone'
import 'vue2-dropzone/dist/vue2Dropzone.min.css'
import InputTag from 'vue-input-tag'

export default {
    components: {
        vueDropzone: vue2Dropzone,
        InputTag
    },
    props: {
        variants: {
            type: Array,
            required: true
        },
        product: {
            type: Array|Object,
            required: false
        },
    },
    data() {
        return {
            back_index:false,
            product_name: '',
            product_sku: '',
            description: '',
            images: [],
            product_variant: [
                {
                    option: this.variants[0].id,
                    tags: []
                }
            ],
            product_variant_prices: [],
            dropzoneOptions: {
                url: '/upload-product-image',
                thumbnailWidth: 150,
                maxFilesize: 0.5,
                headers: {
                    "X-CSRF-TOKEN": document.head.querySelector("[name=csrf-token]").content
                }
            },
            message:'',
        }
    },
    methods: {
        // it will push a new object into product variant
        newVariant(event) {
            event.preventDefault();
            let all_variants = this.variants.map(el => el.id)
            let selected_variants = this.product_variant.map(el => el.option);
            let available_variants = all_variants.filter(entry1 => !selected_variants.some(entry2 => entry1 == entry2))

            this.product_variant.push({
                option: available_variants[0],
                tags: []
            })
        },

        // check the variant and render all the combination
        checkVariant() {
            let tags = [];
            this.product_variant_prices = [];
            this.product_variant.filter((item) => {
                tags.push(item.tags);
            })

            this.getCombn(tags).forEach(item => {
                this.product_variant_prices.push({
                    title: item,
                    price: 0,
                    stock: 0
                })
            })
        },

        // combination algorithm
        getCombn(arr, pre) {
            pre = pre || '';
            if (!arr.length) {
                return pre;
            }
            let self = this;
            let ans = arr[0].reduce(function (ans, value) {
                return ans.concat(self.getCombn(arr.slice(1), pre + value + '/'));
            }, []);
            return ans;
        },

        // store product into database
        saveProduct(event) {

            event.preventDefault();
            let product = {
                title: this.product_name,
                sku: this.product_sku,
                description: this.description,
                product_image: this.images,
                product_variant: this.product_variant,
                product_variant_prices: this.product_variant_prices
            };

            if(this.product){
                console.log('product.id',`${this.product.id}`)
                axios.put(`/product/${this.product.id}`, product).then(res => {
                    if(res.status == 200){
                        this.message = res.data.message;
                        if(this.back_index) window.location.href = '/product';
                    }
                }).catch(error => {
                    console.log(error);
                })
            }else {
                axios.post('/product', product).then(res => {
                    if(res.status == 200){
                        this.message = res.data.message;
                        this.formReset();
                        if(this.back_index) window.location.href = '/product';
                    }
                }).catch(error => {
                    console.log(error);
                })
            }
        },

        // vdropzone Success
        vdropzoneSuccess(file, response){
            this.images.push(response);
        },

        // vdropzone attach
        fileAttachDropzone(){
            let file = { size: 123, type: "image/png" };
            if(this.product && this.product.product_image){
                this.product.product_image.forEach(image=>{
                    this.$refs.myVueDropzone.manuallyAddFile(file, '/'+image.file_path);
                });

            }
        },

        //Set data edit page
        setEditProductData(){
            this.product_name = this.product.title;
            this.product_sku = this.product.sku;
            this.description = this.product.description;
            let productVariants = [];
            this.variants.every((v) => {
                let tags = [];
                let product_variants = this.product.product_variants.filter((t) => t.variant_id == v.id);

                product_variants.every((e) => {
                    tags.push(e.variant);
                    return true;
                });

                if(product_variants.length > 0){
                    productVariants.push({
                        option: v.id,
                        tags : tags
                    });
                }

                return true;
            });
            this.product_variant = productVariants;
            this.product_variant_prices = this.product.product_variant_prices.map((vp) => {
                let title = ((vp.variant_one)?vp.variant_one.variant : '') + ((vp.variant_two)? '/'+vp.variant_two.variant : '') + ((vp.variant_three)? '/' + vp.variant_three.variant : '');

                vp.title = title;
                return vp;
            });
        },

        // Form reset
        formReset(){
            this.product_variant = [
                {
                    option: this.variants[0].id,
                    tags: []
                }
            ];
            this.product_variant_prices = [];
            this.product_name = '';
            this.product_sku = '';
            this.description = '';
            this.images = [];
            this.$refs.myVueDropzone.removeAllFiles();
        }

    },
    mounted() {
        if(this.product){
            this.setEditProductData();
        }
    }
}
</script>
