import DashboardController from './DashboardController'
import AccountController from './AccountController'
import TransactionController from './TransactionController'
import CategoryController from './CategoryController'
import CategoryRuleController from './CategoryRuleController'
import ImportProviderController from './ImportProviderController'
import ImportController from './ImportController'
import ShellPlaceholderController from './ShellPlaceholderController'
import Settings from './Settings'

const Controllers = {
    DashboardController: Object.assign(DashboardController, DashboardController),
    AccountController: Object.assign(AccountController, AccountController),
    TransactionController: Object.assign(TransactionController, TransactionController),
    CategoryController: Object.assign(CategoryController, CategoryController),
    CategoryRuleController: Object.assign(CategoryRuleController, CategoryRuleController),
    ImportProviderController: Object.assign(ImportProviderController, ImportProviderController),
    ImportController: Object.assign(ImportController, ImportController),
    ShellPlaceholderController: Object.assign(ShellPlaceholderController, ShellPlaceholderController),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers