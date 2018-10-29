<template>
    <div class="v-login">
        <h1 class="title">爱豆子数据系统</h1>

        <!-- 登录表单 -->
        <el-form 
            :model="loginForm" 
            :rules="loginRules" 
            ref="loginForm"
            class="loginForm"
            label-width="80px">

            <el-form-item label="用户名" prop="username">
                <el-input v-model="loginForm.username" size="small" @focus="formErrTxt=''"></el-input>
            </el-form-item>

            <el-form-item label="密码" prop="password">
                <el-input type="password" v-model="loginForm.password" size="small" @focus="formErrTxt=''"></el-input>
            </el-form-item>

            <el-form-item label="验证码" prop="msgCode" class="msg-wrap">
                <el-input v-model="loginForm.msgCode" size="small" @focus="formErrTxt=''"></el-input>
            </el-form-item>

            <el-button size="small" 
                type="primary"
                class="msg-btn"
                :disabled="isMsgBtnDisable"
                @click="getMsgCode">
                {{msgBtnTxt}}
            </el-button>

            <el-button size="small" type="primary" class="login-btn" @click="checkMsgCode">登  录</el-button>

            <p class="error-tip">{{formErrTxt}}</p>
        </el-form>
    </div>
</template>

<script>
import http from '@/http/login/http' // 登录http配置

export default {
    props: {
        userName: String
    },

    data() {
        let isDev = location.href.indexOf('-dev') !== -1 ? true : false

        return {
            isHasMsg: false, // 是否已经发送过短信验证码
            isMsgBtnDisable: false, // 发送验证码禁用状态
            msgBtnTxt: '获取验证码', // 获取验证码按钮文字
            loginForm: { // 登录表单
                username: '', // 账号
                password: '', // 密码
                msgCode: isDev ? '123457' : '', // 验证码
            },
            formErrTxt: '', // 表单错误消息
            loginRules: { // 登录表单验证
                username: [
                    { required: true, message: '用户名不能为空', trigger: 'blur' }
                ],
                password: [
                    { required: true, message: '密码不能为空', trigger: 'blur' }
                ],
                msgCode: [
                    { required: true, message: '验证码不能为空', trigger: 'blur' }
                ]
            },
        };
    },

    // 路由进入钩子
    beforeRouteEnter(to, from, next) {
        // 存储进入验证 用于登录成功后跳转
        sessionStorage.setItem('lastPage', from.path);
        next();
    },

    methods: {
        // 验证码倒计时函数
        timeCountDown() {
            let _this = this,
                restTime = 60;

            let timer = setInterval(() => {
                restTime--;

                if(restTime === 0) {
                    _this.isMsgBtnDisable = false;
                    _this.msgBtnTxt = '获取验证码';
                    clearInterval(timer);
                } else {
                    _this.msgBtnTxt = `重新获取（${restTime}）`
                }
            }, 1000);
        },

        // 获取验证码函数
        getMsgCode() {
            let _this = this,
                loginForm = _this.loginForm,
                username = loginForm.username.trim(),
                password = loginForm.password.trim(),
                formErrTxt = '';

            if(username === '') {
                formErrTxt = '请输入用户名';
            } else if(password === '') {
                formErrTxt = '请输入密码';
            }

            if(formErrTxt) {
                _this.formErrTxt = formErrTxt;
                return;
            }

            _this.isMsgBtnDisable = true;

            http.getMsgCode({
                username,
                password
            }).then(() => {
                // 用户已获取验证码
                _this.isHasMsg = true;

                // 获取验证码倒计时
                _this.timeCountDown(); 
            }).catch(() => {
                _this.isMsgBtnDisable = false;
            })
        },

        // 验证验证码 登录
        checkMsgCode() {
            let _this = this,
                formErrTxt = '',
                msgCode = _this.loginForm.msgCode.trim();

            if(msgCode === '') {
                formErrTxt = '请输入验证码';
            } else if(!_this.isHasMsg) {
                formErrTxt = '请先获取验证码';
            }

            if(formErrTxt) {
                _this.formErrTxt = formErrTxt;
                return;
            }
          
            http.checkMsgCode({
                msgCode,
            }).then((msg) => {
                // 保存用户登录状态
                sessionStorage.setItem('userName', msg);

                _this.$emit('update:userName', msg);

                let path = sessionStorage.getItem('lastPage');

                if(path !== '/') {
                    _this.$router.push(path);
                } else {
                    _this.$router.push('/index/seo/EffectReport');
                }
            });
        } 
    }
}
</script>

<style lang="less" scoped>
@import './../../assets/less/theme.less';

.v-login {
    width: 100vw;
    height: 100vh;
    background: url('http://static-10006892.file.myqcloud.com/dataSystem/img/bg-min.jpg');

    .title {
        padding-top: 110px;
        text-align: center;
        color: #fff;
        font-size: 26px;
    }

    .el-form {
        position: fixed;
        top: 300px;
        left: 50%;
        width: 340px;
        margin: -134px 0 0 -170px;
        padding: 25px 10px 0 10px;
        border-radius: 5px;
        background: #fff;

        .el-form-item {
            width: 300px;
            margin-bottom: 15px;
        }

        .msg-wrap {
            display: inline-block;
            width: 180px;
        }

        .msg-btn {
            width: 110px;
            margin-left: 10px;
        }

        .login-btn {
            width: 220px;
            margin: 10px 0 0 80px;
        }

        .error-tip {
            height: 30px;
            text-align: center;
            line-height: 30px;
            color: #f56c6c;
            font-size: 14px;
        }
    }
}
</style>


