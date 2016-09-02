# cscart payment development

## 数据库payment存储
* cscart_payment_processors 表名
* processor_id ID
* processor 名称
* processor_script 支付代码文件，在`/app/payments`目录中
* processor_template 支付模板，在`/design/themes/[Theme name]/templates/`, `/design/themes/responsive/templates/`, `/design/backend/templates/`这三个目录均有（加载优先级？）
* admin_template 管理模板，在`/design/backend/templates/views/payments/components/cc_processors`目录中，用于设置payment的一些基本参数，用户名密码之类的
* ./var/langs/en/core.po是多语言翻译
* callback (Y/N)
* type (P)
* addon (Null)

测试卡号
4111 1111 1111 1111
cvv 111
其他项目随便填

## 安装方法
### 将文件复制到对应的目录中
tuofu.php -> ./app/payments
tuofu.tpl -> ./design/backend/template/views/payments/components/cc_processors
tuofu.po -> ./var/langs/en/addons
