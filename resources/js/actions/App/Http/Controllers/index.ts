import DashboardController from './DashboardController'
import AccountController from './AccountController'
import PositionController from './PositionController'
import TransactionController from './TransactionController'
import TransferController from './TransferController'
import RecurringController from './RecurringController'
import CategoryController from './CategoryController'
import CategoryRuleController from './CategoryRuleController'
import ImportProviderController from './ImportProviderController'
import ImportController from './ImportController'
import InvestmentsController from './InvestmentsController'
import Settings from './Settings'

const Controllers = {
    DashboardController: Object.assign(DashboardController, DashboardController),
    AccountController: Object.assign(AccountController, AccountController),
    PositionController: Object.assign(PositionController, PositionController),
    TransactionController: Object.assign(TransactionController, TransactionController),
    TransferController: Object.assign(TransferController, TransferController),
    RecurringController: Object.assign(RecurringController, RecurringController),
    CategoryController: Object.assign(CategoryController, CategoryController),
    CategoryRuleController: Object.assign(CategoryRuleController, CategoryRuleController),
    ImportProviderController: Object.assign(ImportProviderController, ImportProviderController),
    ImportController: Object.assign(ImportController, ImportController),
    InvestmentsController: Object.assign(InvestmentsController, InvestmentsController),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers