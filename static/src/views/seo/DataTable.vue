<template>
    <div class="v-data-table">
        <el-breadcrumb separator="/">
            <el-breadcrumb-item>SEO数据</el-breadcrumb-item>
            <el-breadcrumb-item>{{pageName}}</el-breadcrumb-item>
        </el-breadcrumb>

        <div class="condition">
            <!-- 来源渠道 -->
            <div class="input-group" v-if="zone !== 0">
                <label class="label">来源渠道</label>
                <el-select 
                    v-model="channelSource" 
                    multiple
                    class="item" 
                    size="small" 
                    @change="filterData"
                    placeholder="全部">
                    <el-option 
                        v-for="(channelObj, index) in channelSourceGroup" 
                        :key="index" 
                        :value="channelObj.channelSource">
                    </el-option>
                </el-select>
            </div>

            <!-- 日期 -->
            <div class="input-group">
                <label class="label">时间</label>
                <el-date-picker 
                    class="item date-range-pick" 
                    v-model="dateSelected" 
                    size="small"
                    :picker-options="pickerOptions"
                    range-separator="至" type="daterange" 
                    :editable="false" 
                    @change="filterData"
                    :clearable="false"
                    placeholder="选择日期范围">
                </el-date-picker>
            </div>

            <!-- 来源频道 -->
            <div class="input-group">
                <label class="label">来源频道</label>
                <el-select 
                    v-model="platformSource" 
                    class="item"
                    @change="filterData"
                    size="small" 
                    placeholder="请选择">
                    <el-option v-for="platformObj in platformSourceGroup"
                        :key="platformObj.label"
                        :label="platformObj.label" 
                        :value="platformObj.value">
                    </el-option>
                </el-select>

                <el-button class="btn" size="small" @click="filterData">确定</el-button>
                <el-button class="btn" size="small" @click="exportData">导出</el-button>
                <el-button class="btn" size="small">
                    <router-link :to="`/index/seo/OriginManage/${zone}`">渠道管理</router-link>
                </el-button>
            </div>
        </div>

        <el-table 
            :data="tableData" 
            :default-sort="{prop: 'dateTime', order: 'descending'}" 
            highlight-current-row
            border
            v-loading="tableLoading"
            stripe
            show-summary
            :summary-method="showSummary"
            @sort-change="handleSortChange"
            size="small">
            <el-table-column prop="dateTime" 
                label="日期" 
                sortable="custom"                 
                min-width="100"
                :formatter="dateFormat"
                show-overflow-tooltip>
            </el-table-column>
            <el-table-column prop="platformSource" 
                label="来源频道" 
                min-width="100" 
                show-overflow-tooltip>
            </el-table-column>
            <el-table-column prop="channelSource" 
                label="来源渠道"
                min-width="100" 
                show-overflow-tooltip>
            </el-table-column>
            <el-table-column prop="pv" 
                label="展现量" 
                sortable="custom"                 
                show-overflow-tooltip>
            </el-table-column>
            <el-table-column prop="uv" 
                label="UV" 
                sortable="custom" 
                min-width="100" 
                show-overflow-tooltip>
            </el-table-column>
            <el-table-column 
                prop="registerNum" 
                label="注册用户数" 
                min-width="100" 
                sortable="custom" 
                show-overflow-tooltip>
            </el-table-column>
            <el-table-column 
                prop="registerNumPercent" 
                label="注册转化率"
                :formatter="percentFormat"
                min-width="100" 
                show-overflow-tooltip>
            </el-table-column>
            <el-table-column prop="bindNum" 
                label="绑定公众号用户数"  
                min-width="100" 
                sortable="custom" 
                show-overflow-tooltip>
            </el-table-column>
            <el-table-column prop="bindNumPercent" 
                label="绑定率" 
                min-width="100" 
                :formatter="percentFormat"                
                show-overflow-tooltip>
            </el-table-column>
            <el-table-column prop="payNum" 
                label="付费用户数" 
                min-width="100" 
                sortable="custom" 
                show-overflow-tooltip>
            </el-table-column>
            <el-table-column prop="payNumPercent" 
                label="付费率" 
                min-width="100" 
                :formatter="percentFormat"                
                show-overflow-tooltip>
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
    </div>
</template>

<script>
import IdouziTools from '@idouzi/idouzi-tools' // idouziTools
import http from '@/http/seo/http' // seo页面http配置
import config from '@/config/config'; // 配置信息

export default {
    props: {
        zone: {
            default: 0,
            type: Number
        },
        pageName: String
    },

    data() {
        return {
            channelSourceGroup: [{ // 来源渠道
                channelSource: ''
            }], 
            channelSource: [], // 已选渠道
            sumList: { // 合计表格
                spv: 0, 
                suv: 0, 
                sregisterNum: 0, 
                sbindNum: 0, 
                spayNum: 0
            },
            dateSelected: [new Date(), new Date()], // 选择日期
            platformSourceGroup: [
            {
                label: '全部',
                value: ''
            },
            {
                label: 'pc',
                value: 'pc'
            }, {
                label: '移动端',
                value: 'phone',
            }], // 来源频道
            platformSource: '', // 已选频道
            tableData: [
                // { // 表格数据
                //     bindNum: 0,
                //     bindNumPercent: 0,
                //     channelSource: '',
                //     dateTime: 0,
                //     payNum: 0,
                //     payNumPercent: 0,
                //     platformSource: '',
                //     pv: 0,
                //     registerNum: 0,
                //     registerNumPercent: 0,
                //     uv: 0,
                // }
            ],
            totalPage: 1, // 总页数
            nowPage: 1, // 当前页
            orderBy: 'date', // 筛选字段
            order: 'asc', // 筛选排序
            tableLoading: false, // 表格loading
            pickDate: [null, null] // element选择的时间
        }
    },

    created() {
        let _this = this;

        // 修改时间插件默认开始时间
        _this.dateSelected[0] = new Date(Date.now() - 6 * 24 * 60 * 60 * 1000);

        if(_this.zone !== 0) {
            // 获取来源渠道数据
            http.getOrigin({
                params: {
                    zone: _this.zone
                }
            }).then(msg => {
                _this.channelSourceGroup = msg.channelSource;
            })
        }

        // 不需要手动触发getList defaultsort会自动触发 
        // _this.getList();
    },

    computed: {
        // element 选择配置
        pickerOptions() {
            let _this = this;

            return {
                disabledDate(time) {
                    let bolean = time.getTime() > new Date().getTime(), // 是否在今天之后
                        pickDate = _this.pickDate;

                    if (_this.pickDate[0] && !_this.pickDate[1]) {
                        let start = _this.pickDate[0].getTime(),
                            oneDay = 24 * 60 * 60 * 1000;
                        
                        // 最大一年时间限制
                        bolean = bolean || Math.abs(time.getTime() - start) >= 365 * oneDay;
                    }
                    return bolean;
                },

                // 更新选择时间
                onPick(dateObj) {
                    // 选择第一个时间
                    if (dateObj.minDate && !dateObj.maxDate) {
                        _this.pickDate[0] = dateObj.minDate;
                        _this.pickDate[1] = null;
                    // 选择第二个时间
                    } else if(dateObj.minDate && dateObj.maxDate) {
                        _this.pickDate[0] = null;
                    }
                }
            }
        }
    },

    methods: {
        /**
         * 百分比格式化
         * @param cellValue {Number} 待格式化小数
         */
        percentFormat(row, column, cellValue, index) {
            let outer = '';

            if(cellValue == 0) {
                outer = 0;
            } else {
                outer = (cellValue * 100).toFixed(2) + '%';
            }

            return outer;
        },

        /**
         * 日期格式化
         * @param cellValue {Number} 待格式化日期字符串
         */
        dateFormat(row, column, cellValue, index) {
            let outer = '';

            if(cellValue) {
                cellValue = cellValue.toString();
                outer = `${cellValue.slice(0, 4)}-
                    ${cellValue.slice(4, 6)}-
                    ${cellValue.slice(6)}`;
            } else {
                outer = '';
            }

            return outer;
        },

        // 显示合计函数
        showSummary() {
            let _this = this,
                sumList = _this.sumList;

            return [
                '合计', '/', '/', 
                sumList.spv, sumList.suv, sumList.sregisterNum, 
                '/', 
                sumList.sbindNum, 
                '/', 
                sumList.spayNum, 
                '/'
            ];
        },

        /**
         * 处理排序
         * @param {Object} sortInfo 排序信息
         */
        handleSortChange(sortInfo) {
            let _this = this;

            _this.orderBy = sortInfo.prop,
            _this.order = sortInfo.order === 'ascending' ? 'asc' : 'desc';

            // 筛选数据
            _this.filterData();
        },

        // 筛选数据
        filterData() {
            let _this = this;

            // 更新分页 手动更改不会触发分页的currentChange
            _this.nowPage = 1;

            // 获取数据
            _this.getList();
        },

        getList() {
            let _this = this,
                params = {
                    page: _this.nowPage,
                    platformSource: _this.platformSource,
                    order: _this.order,
                    orderBy: _this.orderBy,
                    sdate: IdouziTools.formatTime(_this.dateSelected[0], 'yyyyMMdd'),
                    edate: IdouziTools.formatTime(_this.dateSelected[1], 'yyyyMMdd'),
                    zone: _this.zone
                };

            if(_this.zone !== 0) {
                params.channelSource = _this.channelSource
            }

            // 排序默认值
            if(!_this.orderBy) {
                params.orderBy = 'dateTime';
                params.order = 'desc';
            }
           
            // 开启loading
            _this.tableLoading = true;

            // 请求列表数据
            http.getSource({
                params,
            }).then((msg) => {
                    _this.totalPage = msg.totalPage; // 总页数赋值
                    _this.tableData = msg.list; // 列表赋值

                    _this.sumList = msg.sumList[0] || {}; // 合计数据

                    // 关闭loading
                    _this.tableLoading = false;
            }).catch(() => {
                // 关闭loading
                _this.tableLoading = false;
            })
        },

        // 导出数据
        exportData() {
            let _this = this,
                params = {
                    platformSource: _this.platformSource,
                    order: 'desc',
                    orderBy: 'dateTime',
                    sdate: IdouziTools.formatTime(_this.dateSelected[0], 'yyyyMMdd'),
                    edate: IdouziTools.formatTime(_this.dateSelected[1], 'yyyyMMdd'),
                    zone: _this.zone
                };

            if(_this.zone !== 0) {
                params.channelSource = _this.channelSource
            }

            if(!_this.tableData.length) {
                _this.$message('未查询到相关数据');
                return;
            }

            let url = config.curLink + '/user/user-source-data-export?';

            for(let key in params) {
                if(key === 'channelSource') {
                    if(params[key].length) {
                        url += `${key}=`;

                        params[key].forEach((item, index) => {
                            url += `${params[key][index]},`;
                        });

                        url = url.slice(0, -1) + '&'; // 去末尾逗号
                    }
                } else if(key !== 'platformSource' || params[key] !== '') {
                    url += `${key}=${params[key]}&`;
                }
            }

            location.href = url.slice(0, -1); // 去末尾 &
        }
    }
}
</script>

<style lang="less" scoped>
@import './../../assets/less/tablePage.less'; // 表格页面基本样式
</style>

