# WordPress 服务器部署与操作手册（舞台灯独立站）

适用项目：`Stage Lighting E-commerce Website`  
技术栈：`WordPress + WooCommerce + Elementor Pro`  
目标：支持 B2C 下单 + B2B 询盘，满足上线、运维、内容运营的标准流程。

---

## 1. 部署目标与交付范围

- 部署可用的 WordPress 生产环境（域名、SSL、缓存、安全、备份）。
- 完成 WooCommerce 电商基础配置（支付、运费、税费、通知邮件）。
- 配置 B2B 询盘入口与表单（含文件上传与自动邮件通知）。
- 建立上线后可执行的日常运维 SOP（更新、备份、监控、故障处理）。

---

## 2. 环境与资源准备

## 2.1 域名与 DNS

- 主域名：建议 `example.com`
- DNS 服务商：Cloudflare / 域名注册商 DNS
- 必备解析：
  - `A` 记录：`@ -> 服务器公网IP`
  - `CNAME`：`www -> @`

## 2.2 服务器建议

- 系统：`Ubuntu 22.04 LTS`
- 规格（起步）：`2 vCPU / 4GB RAM / 80GB SSD`
- 软件：`Nginx + PHP 8.2 + MySQL 8.0 + Redis(可选)`
- 机房：优先北美或欧洲，贴近核心客户区域

## 2.3 对外服务清单

- HTTPS 证书：Let’s Encrypt 或 Cloudflare SSL
- 邮件发送：SendGrid / Mailgun / Amazon SES
- CDN：Cloudflare（推荐）
- 对象存储（可选）：用于媒体资源分发

---

## 3. 一次性部署步骤（生产环境）

## 3.1 初始化系统与安全

1. 创建非 root 管理员账号，禁用 root 远程登录。
2. 配置防火墙（仅开放 `22/80/443`）。
3. 安装 fail2ban（可选）与基础安全策略。
4. 设置系统时区与自动安全更新。

## 3.2 安装运行环境

1. 安装 Nginx、MySQL、PHP 8.2（含常用扩展）。
2. 创建数据库与专用数据库账号。
3. 安装并配置 WordPress 目录权限。
4. 配置 Nginx 虚拟主机并绑定域名。

## 3.3 安装 WordPress

1. 部署 WordPress 到网站根目录。
2. 完成安装向导（站点名、管理员账号、语言）。
3. 开启伪静态（固定链接建议 `%postname%`）。

## 3.4 安装主题与插件

核心插件建议：

- 电商：`WooCommerce`
- 页面：`Elementor Pro`
- SEO：`Rank Math`
- 安全：`Wordfence`
- 缓存：`LiteSpeed Cache` 或 `WP Rocket`
- 备份：`UpdraftPlus`
- 表单：`WPForms Pro`
- 客服：`Tawk.to` / `Tidio`
- 社交登录：`Nextend Social Login`（Google / Facebook）

---

## 4. 代理要求（安装命令统一规范）

你要求所有安装命令带代理，统一使用：

- `--proxy=http://10.144.1.10:8080`

常见示例：

```bash
# pip
pip install requests --proxy=http://10.144.1.10:8080

# npm
npm install axios --proxy=http://10.144.1.10:8080

# curl 下载
curl -x http://10.144.1.10:8080 -O https://wordpress.org/latest.zip
```

说明：

- WordPress 后台直接安装插件/主题通常不走 `--proxy` 参数，若服务器网络受限，建议在系统层配置代理环境变量或通过离线包上传安装。
- 若使用 WP-CLI 拉取资源，优先使用已配置代理的系统网络环境执行。

---

## 5. WordPress 后台配置标准

## 5.1 基础设置

- 站点语言：English
- 时区：业务所在时区（建议 UTC）
- 媒体：关闭自动生成过多尺寸（按主题需要）
- 固定链接：`/%postname%/`

## 5.2 WooCommerce 配置

- 币种：USD（可按业务增加 EUR）
- 支付：PayPal + Stripe
- 运费：按重量/地区模板
- 税费：按目标市场配置 VAT/GST
- 邮件模板：统一品牌风格，配置发件域名 SPF/DKIM

推荐一键基线脚本（已内置）：

```bash
# 调试环境
bash scripts/wp-configure-commerce-rules.sh

# 生产环境
bash scripts/wp-configure-commerce-rules.sh --prod

# 指定规则模板（可选）
bash scripts/wp-configure-commerce-rules.sh --rules config/commerce-rules.prod.json
```

脚本会自动：

- 开启税费计算
- 同步运费分区（US / Europe / Asia Pacific）
- 设置基础运费规则（Flat Rate + Free Shipping）
- 写入基础税率（EU VAT / UK VAT / AU GST / SG GST）
- 自动按环境读取规则：
  - `config/commerce-rules.debug.json`
  - `config/commerce-rules.prod.json`
- 也可通过 `--rules` 指定自定义规则文件

## 5.3 B2B 询盘配置

- 导航增加 `For Business / Bulk Order`
- 产品页增加 `Request Bulk Quote` 按钮
- 表单字段：
  - Full Name
  - Company Name
  - Email
  - Phone
  - Country
  - Interested Product(s)
  - Estimated Quantity
  - Project Description
  - Upload File
- 通知规则：
  - 提交后发给销售组邮箱
  - 自动回执给客户邮箱
  - 每日自动发送“超期线索提醒邮件”（WP-Cron）
- 管理入口：
  - `Settings -> Stage B2B Settings` 可手动点击 `Send Reminder Now` 立即发送提醒
  - `B2B Leads` 列表中 `Overdue` 列用于快速识别超期线索

---

## 6. 上线检查清单（Go-Live Checklist）

- [ ] 所有页面可访问且无 404
- [ ] 移动端与桌面端显示正常
- [ ] 购物流程完整（加购/结算/支付回调）
- [ ] 运费与税费计算正确
- [ ] B2B 表单可提交，附件可接收
- [ ] 邮件通知正常（管理员 + 客户）
- [ ] 社交登录（Google/Facebook）配置完成并可用
- [ ] 社交登录 OAuth 回调域名与站点域名一致
- [ ] Live Chat（Tawk/Tidio/Custom）已配置并可触发
- [ ] HTTPS 生效，无混合内容告警
- [ ] SEO 基础项完成（Title/Description/Sitemap/Robots）
- [ ] GA4 与 Meta Pixel 已埋点并验证
- [ ] 全站备份与回滚测试完成

### 6.1 自动验收脚本（推荐）

上线前建议执行：

```bash
# 调试环境
bash scripts/site-audit.sh

# 生产环境
bash scripts/site-audit.sh --prod
```

脚本会检查：

- 主题、关键插件、关键页面、菜单绑定
- WooCommerce 运费/税费基础配置
- 社交登录插件激活与基础配置
- 社交登录回调域名一致性（`[WARN]` 为提示，不阻塞）
- Live Chat 配置状态

---

## 7. 日常运维 SOP（给运营/管理员）

## 7.1 每日

- 检查订单状态、支付异常、邮件队列。
- 检查安全告警（Wordfence、主机监控、CDN 告警）。
- 处理 B2B 询盘并登记跟进状态。
- 检查是否收到“超期线索提醒邮件”；若未收到，可在 `Stage B2B Settings` 手动触发一次验证。

## 7.2 每周

- 更新产品库存、价格、重点活动 Banner。
- 发布至少 1 篇 Blog（SEO 内容）。
- 检查表单转化率与页面跳出率。

## 7.3 每月

- 插件/主题/核心更新（先测试后生产）。
- 恢复演练：从备份恢复到测试环境验证可用。
- 分析 SEO 与广告投放数据，优化落地页。

---

## 8. 版本更新与变更流程

1. 先在 Staging（测试站）执行更新。
2. 完成冒烟测试（首页、产品页、结算、询盘表单）。
3. 业务低峰窗口发布到生产。
4. 发布后 30 分钟内重点监控错误日志与支付状态。
5. 若异常无法快速修复，按备份策略回滚。

---

## 9. 权限与账号管理

- 管理员账号仅限 1-2 人，启用 2FA。
- 编辑、客服、运营使用最小权限角色。
- 禁止多人共享同一管理员账号。
- 重要操作（支付、插件更新）需记录操作日志。

---

## 10. 备份与灾备策略

- 备份范围：数据库 + `wp-content` + 配置文件。
- 频率建议：
  - 数据库：每日
  - 媒体与代码：每周
- 存储策略：本地 + 异地（云存储）
- 保留周期：7日、30日、90日分层保留
- RTO 目标：2 小时内恢复  
- RPO 目标：不超过 24 小时数据损失

---

## 11. 常见问题处理（FAQ）

1. 后台打不开  
   - 检查 Nginx/PHP-FPM 状态、磁盘空间、错误日志。

2. 插件更新后白屏  
   - 通过 SFTP 或命令行禁用异常插件，回滚备份。

3. 邮件收不到  
   - 检查 SMTP 配置、发件域名 DNS（SPF/DKIM/DMARC）、垃圾箱。

4. 站点变慢  
   - 清缓存、检查图片体积、开启 CDN、排查慢 SQL。

---

## 12. 角色分工建议

- 开发/技术：部署、升级、性能优化、安全防护。
- 运营：上新、内容发布、基础 SEO、活动配置。
- 销售：处理询盘、跟进客户、维护案例素材。
- 管理者：审核发布、权限审批、关键数据复盘。

---

## 13. 交付物建议（对外）

- 《站点账号与权限交接表》
- 《插件与版本清单》
- 《备份与回滚操作指南》
- 《内容发布规范（产品/博客）》
- 《故障应急联系人与升级路径》

---

如需，我可以下一步继续输出两份配套文档：

1. 《WordPress 后台操作手册（给运营，含截图位说明）》  
2. 《服务器部署命令手册（Ubuntu + Nginx + PHP + MySQL + SSL）》
