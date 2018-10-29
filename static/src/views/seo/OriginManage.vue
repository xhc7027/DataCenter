<template>
    <div class="v-origin-manage">
        <el-breadcrumb separator="/">
            <el-breadcrumb-item>SEO数据</el-breadcrumb-item>
            <el-breadcrumb-item>渠道管理</el-breadcrumb-item>
        </el-breadcrumb>

        <div class="condition">
            <!-- 来源频道 -->
            <div class="input-group">
                <label class="label">来源分区</label>
                <el-select 
                    v-model="selectedZone" 
                    class="item"                    
                    @change="filterData"
                    size="small">
                    <el-option label="全部" value="-1"></el-option>
                    <el-option v-for="(zoneObj, index)  in zoneGroup"
                        :label="zoneObj.label"
                        :key="index"
                        :value="zoneObj.value">
                    </el-option>
                </el-select>
            </div>

            <el-button 
                class="btn" 
                @click="addOrigin" 
                size="small">
                添加渠道
            </el-button>
        </div>

        <el-table 
            :data="tableData" 
            highlight-current-row
            border
            v-loading="tableLoading"
            stripe
            size="small">
            <el-table-column prop="channelSource" 
                label="渠道名称" 
                show-overflow-tooltip>
            </el-table-column>
            <el-table-column prop="refererUrl" 
                label="渠道链接" 
                show-overflow-tooltip>
            </el-table-column>
            <el-table-column prop="zoneId" 
                label="操作" 
                align="center">
                <template slot-scope="scope">
                    <el-button
                        class="btn"
                        size="small"
                        @click="editOrigin(scope.row)">
                        编辑
                    </el-button>
                    <el-button
                        class="btn"
                        size="small"
                        type="danger"
                        @click="showConfirmDialog(scope.row.zoneId)">
                        删除
                    </el-button>
                </template>
            </el-table-column>
        </el-table>

        <!--分页-->
        <el-pagination @current-change="getList"
                       :current-page.sync="nowPage"
                       :page-size="1"
                       v-if="totalPage > 1"
                       layout="prev, pager, next"
                       :total="totalPage">
        </el-pagination>

        <!-- 二次确认弹窗 -->
        <el-dialog
            title="提示"
            :visible.sync="isConfirmDialog"
            width="350px">
            <p class="confirm-txt">确认删除？</p>
            <span slot="footer" class="dialog-footer">
                <el-button @click="isConfirmDialog = false" size="small">取 消</el-button>
                <el-button type="primary" @click="delOrigin" size="small">确 定</el-button>
            </span>
        </el-dialog>

        <!-- 编辑来源弹窗 -->
        <el-dialog
            :title="editOrAdd === 'add' ? '添加渠道': '编辑渠道'"
            :visible.sync="isEditDialog"
            width="450px">
            <div class="dialog-content">
                <el-form 
                    :model="editForm" 
                    :rules="rules" 
                    ref="editForm" 
                    label-width="100px">

                    <el-form-item label="渠道名称" prop="channelSource">
                        <el-input v-model="editForm.channelSource" size="small"></el-input>
                    </el-form-item>

                    <el-form-item label="渠道链接" prop="refererUrl">
                        <el-input v-model="editForm.refererUrl" size="small"></el-input>
                    </el-form-item>

                    <!-- 渠道分区选择 -->
                    <el-form-item label="所属分区"
                        prop="zone" 
                        v-if="editOrAdd === 'add' ? true : false" >
                        <el-select v-model="editForm.zone" size="small">
                            <el-option v-for="zoneObj in zoneGroup"
                                :label="zoneObj.label"
                                :key="zoneObj.label"
                                :value="zoneObj.value">
                            </el-option>
                        </el-select>
                    </el-form-item>
                </el-form>
            </div>

            <span slot="footer" class="dialog-footer">
                <el-button @click="isEditDialog = false" 
                    size="small">
                    取 消
                </el-button>
                <el-button type="primary" 
                    @click="submitForm" 
                    :loading="submitLoding" 
                    size="small">
                    确 定
                </el-button>
            </span>
        </el-dialog>
    </div>
</template>

<script>
import http from '@/http/seo/http' // seo页面http

export default {
    data() {
        // url正则
        let reg = /(http|https):\/\/[\w\-_]+(\.[\w\-_]+)+([\w\-\.,@?^=%&amp;:/~\+#]*[\w\-\@?^=%&amp;/~\+#])?/,
            checkLink = (rule, value, callback) => {
                if (!reg.test(value)) {
                    callback(new Error('请输入正确链接'));
                } else {
                    callback();
                }
            }

        return {
            zoneGroup: [{
                label: '直接访问',
                value: '0'
            }, {
                label: '搜索引擎',
                value: '1'
            }, {
                label: '外链',
                value: '2'
            }, {
                label: '自媒体',
                value: '3'
            }],
            selectedZone: '', // 所选分区
            tableData: [{ // 表格数据
                channelSource: 0, // 渠道名称
                refererUrl: '', // 渠道链接
                zoneId: '' // 渠道ID
            }],
            totalPage: 1, // 总页数
            nowPage: 1, // 当前页
            tableLoading: false, // 表格loading
            isConfirmDialog: false, // 二次确认弹窗
            isEditDialog: false, // 编辑渠道弹窗
            editZoneId: 0, // 编辑渠道ID
            editForm: { // 编辑表单
                channelSource: '', // 渠道名称
                refererUrl: '', // 渠道链接
                zone: '', // 渠道ID
            },
            editOrAdd: 'edit', // 弹窗来自新建或编辑
            submitLoding: false, // 提交编辑表单loading
            rules: { // 表单验证规则
                channelSource: [
                    { 
                        required: true, 
                        message: '渠道名称不能为空', 
                        trigger: 'blur' 
                    },
                ],
                refererUrl: [
                    { 
                        required: true, 
                        message: '链接不能为空', 
                        trigger: 'blur' 
                    },
                    // { 
                    //     validator: checkLink, 
                    //     trigger: 'blur' 
                    // },
                ],
                zone: [
                    { 
                        required: true, 
                        message: '请选择分区', 
                        trigger: 'change' 
                    },
                ]
            }
        }
    },

    created() {
        let _this = this,
            zone = _this.$route.params.zone;

        _this.selectedZone = _this.$route.params.zone;

        // 获取表格数据
        _this.getList();
    },

    // 路由更新
    beforeRouteUpdate (to, from, next) {
        let _this = this;
        
        _this.selectedZone = to.params.zone;

        // 获取表格数据
        _this.getList();

        next();
    },

    methods: {
        // 筛选数据
        filterData() {
            let _this = this;

            // 更新分页 手动更改不会触发分页的currentChange
            _this.nowPage = 1;

            // 获取数据
            _this.getList();
        },

        // 获取数据列表
        getList() {
            let _this = this,
                params = {
                    page: _this.nowPage,
                    zone: _this.selectedZone == -1 ? '' : _this.selectedZone
                };

            // 开启loading
            _this.tableLoading = true;

            // 请求列表数据
            http.getOriginList({
                params: params
            }).then((msg) => {
                    _this.totalPage = msg.totalPage; // 总页数赋值
                    _this.tableData = msg.list; // 列表赋值

                    // 关闭loading
                    _this.tableLoading = false;
            }).catch(() => {
                // 关闭loading
                _this.tableLoading = false;
            })
        },

        /**
         * 显示确认删除弹窗
         * @param {String} zoneId 渠道ID 
         */
        showConfirmDialog(zoneId) {
            let _this = this;

            _this.editZoneId = zoneId;
            _this.isConfirmDialog = true;
        },

        // 添加渠道
        addOrigin() {
            let _this = this,
                editForm = _this.editForm;

            // 重置添加表单
            editForm.channelSource = '';
            editForm.refererUrl = '';

            _this.editOrAdd = 'add';
            _this.isEditDialog = true;
        },

        /**
         * 编辑渠道
         * @param 当前行数据 {Object} row 
         */
        editOrigin(row) {
            let _this = this,
                editForm = _this.editForm;

            // 将渠道信息填入表单
            editForm.channelSource = row.channelSource;
            editForm.refererUrl = row.refererUrl;

            _this.editZoneId = parseInt(row.zoneId);
            _this.editOrAdd = 'edit';
            _this.isEditDialog = true;

            // 去除表单错误
            _this.$nextTick(() => {
                _this.$refs.editForm.clearValidate();
            })
        },

        // 删除渠道
        delOrigin() {
            let _this = this;
            
            _this.isConfirmDialog = false;

            http.delOrigin({
                data: {
                    zoneId: parseInt(_this.editZoneId)
                }
            }).then(() => {
                _this.$message('删除成功');
                _this.filterData();
            })
        },

        // 提交表单 包括添加 编辑渠道
        submitForm() {
            let _this = this;

            _this.$refs.editForm.validate((valid) => {
                if (!valid) {
                    return false;
                }

                _this.submitLoding = true;

                // 来自添加
                if(_this.editOrAdd === 'add') {
                    let editForm = _this.editForm,
                        data = {
                            channelSource: editForm.channelSource,
                            refererUrl: editForm.refererUrl,
                            zone: editForm.zone
                        };

                    http.addOrigin(data).then(() => {
                        _this.$message('添加成功');
                        _this.filterData();
                        _this.submitLoding = false;
                        _this.isEditDialog = false;
                    }).catch(() => {
                        _this.submitLoding = false;
                    })

                // 来自编辑
                } else {
                    let editForm = _this.editForm,
                        data = {
                            channelSource: editForm.channelSource,
                            refererUrl: editForm.refererUrl,
                            zoneId: _this.editZoneId
                        };

                    http.editOrigin(data).then(() => {
                        _this.$message('编辑成功');
                        _this.filterData();
                        _this.submitLoding = false;
                        _this.isEditDialog = false;
                    }).catch(() => {
                        _this.submitLoding = false;
                    })
                }
            })
        }
    }
}
</script>

<style lang="less" scoped>
@import './../../assets/less/theme.less';
@import './../../assets/less/tablePage.less'; // 表格页面基本样式

.v-origin-manage {
    .el-form {
        .el-form-item:last-child {
            margin-bottom: 0;
        }
    }

    .confirm-txt {
        text-align: center;
    }
}
</style>

