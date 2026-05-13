# WordPress 后台操作手册（运营版）

适用对象：运营、客服、销售支持  
站点类型：舞台灯独立站（B2C + B2B）

---

## 1. 登录与角色权限

- 后台入口：`https://你的域名/wp-admin`
- 建议角色：
  - Administrator：仅技术/负责人
  - Shop Manager：电商与订单管理
  - Editor：页面与博客内容维护
- 要求：
  - 必开强密码 + 2FA
  - 禁止共享管理员账号

---

## 2. 首次初始化（一次性）

1. `Appearance -> Themes` 激活 `Stage Lighting Theme`
2. `Plugins` 激活：
   - `Stage Lighting B2B Quote`
   - `Stage Lighting Site Setup`
3. 打开 `Tools -> Stage Lighting Setup`
4. 点击 `Run Initialization`

系统会自动创建：

- 页面：Home / Products / Solutions / Projects / For Business / About / Blog / Contact 等
- 菜单：主导航和页脚导航
- 商品分类：5个一级类
- 场景标签：7个应用场景
- 商品属性：Power / Application / Control Protocol / Certification
- 示例商品：`Beam Moving Head 350W`

---

## 3. 商品管理（WooCommerce）

## 3.1 新增商品

路径：`Products -> Add New`

必填建议：

- Product Name（英文）
- Main Image（白底图）+ Gallery（场景图）
- Short Description（3-5行卖点）
- Long Description（参数、应用、售后）
- Price / Stock / SKU
- Category（五大类）
- Tags（应用场景）

## 3.2 商品信息模板建议

- 功率：如 `350W`
- 控制协议：如 `DMX512`
- 光束角/色温/CRI（按设备类型补充）
- 电压与频率
- 认证：`CE/FCC/RoHS/UL`
- 包装尺寸与重量

---

## 4. B2B 询盘管理

## 4.1 入口说明

- 页面入口：`For Business`
- 商品页入口：`Request Bulk Quote`（自动带入产品名）

## 4.2 表单字段（已配置）

- Full Name
- Company Name
- Email Address
- Phone Number
- Country
- Interested Product(s)
- Estimated Quantity
- Project Description
- Upload File

## 4.3 提交后的处理流程

1. 销售邮箱收到询盘通知
2. 客户自动收到回执邮件
3. 后台 `B2B Leads` 自动生成线索记录
4. 运营在 `B2B Leads` 中更新状态：`new/contacted/quoted/won/lost`
5. 在每条线索详情中维护 `Follow-up Date`（建议首次设置为提交后2天内）
6. 可在 `B2B Leads` 列表按状态和跟进日期区间筛选，并使用 `Export CSV` 导出当前筛选结果
7. 优先处理 `Overdue=YES` 的线索
8. 运营在 CRM/表格中登记线索并分配负责人
9. 24小时内首次回复

## 4.4 联系方式配置（后台可改）

路径：`Settings -> Stage B2B Settings`

- Sales Email（询盘收件邮箱）
- WhatsApp Number（仅数字，含国家码）
- WhatsApp Display Text（前台展示文案）

说明：

- 页脚联系方式与右下角 WhatsApp 浮动按钮会自动读取上述配置。
- 建议上线前先完成此配置再做验收。

---

## 5. 页面与菜单维护

## 5.1 页面编辑

路径：`Pages -> All Pages`

- 首页：建议用 Elementor 维护模块区块
- For Business：保留表单短代码 `[stage_bulk_quote_form]`
- Projects/Blog：持续更新案例与内容营销

## 5.2 菜单编辑

路径：`Appearance -> Menus`

- 主导航：Home / Products / Solutions / Projects / For Business / About / Blog / Contact
- 页脚：For Business / OEM-ODM / Downloads / Contact

---

## 6. 订单与客户服务

## 6.1 订单处理

路径：`WooCommerce -> Orders`

- 新订单：确认支付、备货、发货
- 异常订单：核对支付状态和地址
- 完成订单：发送物流单号与售后说明

## 6.2 常见客服回复模板（建议）

- 报价时效：24h 内
- 样品政策：说明起订量与样品运费
- 定制能力：OEM/ODM 支持范围

---

## 7. 内容运营节奏（SEO）

- 每周至少发布 1 篇 Blog
- 每月复盘高流量页面并优化 CTA
- 重点文章类型：
  - Buying Guide
  - Tech Explainer
  - Case Study
  - Industry Trends
  - Installation Tutorial

---

## 8. 安全与更新 SOP

- 每周：
  - 检查插件更新和安全告警
  - 先测试站验证，再更新生产站
- 每日：
  - 检查订单、询盘、邮件投递状态
- 每月：
  - 做一次备份恢复演练

---

## 9. 常见问题

1. 看不到询盘按钮  
   - 检查插件 `Stage Lighting B2B Quote` 是否启用

2. 询盘提交后没收到邮件  
   - 检查 SMTP 插件、发件域名 SPF/DKIM

3. 商品页样式错乱  
   - 清除缓存插件 + CDN 缓存后重试

