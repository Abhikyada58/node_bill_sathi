import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import type { LucideIcon } from "lucide-react";

export function ModulePlaceholder({
  title,
  description,
  icon: Icon,
}: {
  title: string;
  description: string;
  icon: LucideIcon;
}) {
  return (
    <div className="grid gap-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
        <p className="text-sm text-muted-foreground">{description}</p>
      </div>
      <Card>
        <CardHeader className="items-center text-center">
          <div className="mx-auto flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
            <Icon className="size-6" />
          </div>
          <CardTitle>Module migration in progress</CardTitle>
          <CardDescription>
            This module is being ported from the legacy PHP app to Next.js in a
            follow-up pass. Auth and the dashboard are already live on the new stack.
          </CardDescription>
        </CardHeader>
        <CardContent />
      </Card>
    </div>
  );
}
