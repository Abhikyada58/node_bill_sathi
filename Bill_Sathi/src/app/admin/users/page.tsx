import { getAdminAllUsers } from "@/actions/admin"
import { Card, CardContent } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Badge } from "@/components/ui/badge"
import { format } from "date-fns"

export default async function AdminUsersPage() {
  const res = await getAdminAllUsers()
  
  if ("error" in res) {
    return <div className="text-red-500 font-bold p-6">Error: {res.error}</div>
  }

  const users = res.users || []

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight text-slate-900">Users & Shops</h1>
        <p className="text-slate-500">Manage all registered users on the platform.</p>
      </div>

      <Card className="border-none shadow-sm overflow-hidden">
        <CardContent className="p-0">
          <Table>
            <TableHeader className="bg-slate-50">
              <TableRow>
                <TableHead className="font-semibold text-slate-700">Shop Name</TableHead>
                <TableHead className="font-semibold text-slate-700">Owner Name</TableHead>
                <TableHead className="font-semibold text-slate-700">Email</TableHead>
                <TableHead className="font-semibold text-slate-700">Mobile</TableHead>
                <TableHead className="font-semibold text-slate-700">Joined Date</TableHead>
                <TableHead className="font-semibold text-slate-700 text-center">Role</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {users.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={6} className="text-center h-24 text-slate-500">
                    No users found.
                  </TableCell>
                </TableRow>
              ) : (
                users.map((u: any) => (
                  <TableRow key={u.id} className="hover:bg-slate-50/50">
                    <TableCell className="font-medium text-slate-900">
                      {u.shop_name || <span className="text-slate-400 italic">Not set</span>}
                    </TableCell>
                    <TableCell>{u.full_name}</TableCell>
                    <TableCell className="text-slate-600">{u.email}</TableCell>
                    <TableCell>{u.shop_mobile || "-"}</TableCell>
                    <TableCell className="text-slate-600">
                      {u.created_at ? format(new Date(u.created_at), "dd MMM yyyy") : "-"}
                    </TableCell>
                    <TableCell className="text-center">
                      {u.is_admin ? (
                        <Badge className="bg-purple-100 text-purple-700 hover:bg-purple-100 border-purple-200">Admin</Badge>
                      ) : (
                        <Badge variant="outline" className="text-slate-600 border-slate-200">User</Badge>
                      )}
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  )
}
